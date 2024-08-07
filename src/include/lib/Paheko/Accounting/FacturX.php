<?php

namespace Paheko\Accounting;

use Paheko\Entity;
use Paheko\Plugins;
use Paheko\Utils;
use Paheko\Static_Cache;
use KD2\DB\Date;

use const Paheko\{ROOT, FACTURX_COMMAND, STATIC_CACHE_ROOT};

/**
 * This class generates a PDF from a HTML output, then appends a XML Factur-X file to it,
 * making sure it is compliant with the specification, using Ghostscript.
 *
 * @see https://www.ghostscript.com/blog/zugferd.html
 */
// TODO: reference here: https://fnfe-mpe.org/factur-x/qui-propose-factur-x/
class FacturX extends Entity
{
	const ROOT = ROOT . '/include/data/factur-x';

	const TYPE_INVOICE = 380;
	const TYPE_REFUND = 381;

	const TYPES = [
		self::TYPE_INVOICE => 'invoice',
		self::TYPE_REFUND => 'refund',
	];

	protected string $number;
	protected int $type = self::TYPE_INVOICE;
	protected Date $issue_date;
	protected string $buyer_ref = '';
	protected string $issuer_assigned_id = '';

	protected string $seller_name;
	protected string $seller_siret;
	protected string $seller_country;
	protected string $seller_vat_number;

	protected string $buyer_name;
	protected string $buyer_siret;
	protected string $buyer_country;

	protected string $currency = 'EUR';
	protected int $total_amount;
	protected int $vat_amount;
	protected int $due_amount;

	protected string $html;

	static public function isAvailable(): bool
	{
		if (Plugins::hasSignal('facturx.create')) {
			return true;
		}

		return FACTURX_COMMAND === 'gs';
	}

	public function selfCheck(): void
	{
		$this->assert(isset($this->html) && strlen($this->html), 'Le contenu HTML de la facture est vide');
		$this->assert(isset($this->number) && strlen($this->number), 'Le numéro de facture n\'est pas renseigné');
		$this->assert(isset($this->seller_name) && strlen($this->seller_name), 'Le nom du vendeur n\'est pas renseigné');
		$this->assert(isset($this->issue_date), 'La date de la facture n\'est pas renseignée');

		if (isset($this->seller_vat_number)) {
			$this->assert(strlen($this->seller_vat_number), 'Le numéro de TVA du vendeur n\'est pas renseigné');
		}
		else {
			// BT-30 : identification légale du vendeur (n° de SIREN / SIRET), donnée Obligatoire si le vendeur
			// n’a pas de Numéro de TVA intracommunautaire, fortement recommandé sinon. Cette donnée
			// fait l’objet d’un attribut venant indiquer le référentiel de l’identification.
			$this->assert(isset($this->seller_siret) && strlen($this->seller_siret), 'Le numéro de SIRET du vendeur n\'est pas renseigné');
		}

		$this->assert(isset($this->seller_country) && strlen($this->seller_country), 'Le pays du vendeur n\'est pas renseigné');
		$this->assert(isset($this->buyer_name) && strlen($this->buyer_name), 'Le nom du client n\'est pas renseigné');

		$this->assert(isset($this->buyer_country) && strlen($this->buyer_country), 'Le pays du client n\'est pas renseigné');
		$this->assert(isset($this->total_amount), 'Le montant HT n\'est pas renseigné');
		$this->assert(isset($this->vat_amount), 'Le montant de la TVA n\'est pas renseigné');
		$this->assert(isset($this->due_amount), 'Le reste à payer n\'est pas renseigné');
		$this->assert(Utils::checkSIRET($this->seller_siret), 'Le numéro de SIRET du vendeur est invalide');
		$this->assert(Utils::checkSIRET($this->buyer_siret), 'Le numéro de SIRET du client est invalide');
		$this->assert($this->currency === 'EUR', 'Seules les factures en euros sont gérées');
		$this->assert(strlen($this->buyer_country) === 2 && ctype_alpha(strtolower($this->buyer_country)), 'Pays du client invalide');
		$this->assert(strlen($this->seller_country) === 2 && ctype_alpha(strtolower($this->seller_country)), 'Pays du vendeur invalide');
	}

	public function save(bool $selfcheck = true): bool
	{
		throw new \LogicException('This entity cannot be saved');
	}

	public function stream(bool $download = false): void
	{
		$this->selfCheck();
		header('Content-Type: application/pdf');
		$name = sprintf('Facture_%s.pdf', preg_replace('/[^\w-]+/', '_', $this->number));
		header(sprintf('Content-Disposition: %s; filename="%s"', $download ? 'attachment' : 'inline', $name));
		echo $this->create(null);
	}

	public function saveTo(string $path): void
	{
		$this->selfCheck();
		$this->create($path);
	}

	protected function create(?string $path): ?string
	{
		$xml = file_get_contents(self::ROOT . '/factur-x.xml');
		$xml = preg_replace_callback('/\{\$([a-z_]+)\}/', function ($match) {
			$key = $match[1];
			if ($key === 'issue_date') {
				return $this->issue_date->format('Ymd');
			}
			elseif ($key === 'total_amount_with_tax') {
				return Utils::money_format($this->total_amount + $this->vat_amount, '.', '', true);
			}
			elseif ($key === 'total_amount' || $key === 'vat_amount' || $key === 'due_amount') {
				return Utils::money_format($this->$key, '.', '', true);
			}
			else {
				return htmlspecialchars($this->$key, ENT_XML1);
			}
		}, $xml);

		$signal = Plugins::fire('facturx.create', ['html' => $this->html, 'xml' => $xml], ['pdf_string' => null]);

		if ($signal) {
			if ($str = $signal->getOut('pdf_string')) {
				if (null === $path) {
					return $str;
				}
				else {
					file_put_contents($path, $str);
					return null;
				}
			}
			else {
				throw new \LogicException('Signal facturx.create did not return a string');
			}
		}

		$id = 'facturx_' . sha1(random_bytes(10));
		Static_Cache::store($id, $xml);
		$tmp_xml_file = Static_Cache::getPath($id);
		$tmp_pdf_file = Utils::filePDF($this->html);

		$cmd = sprintf('gs --permit-file-read=%s'
			. ' -sDEVICE=pdfwrite'
			. ' -dPDFA=3'
			. ' -sColorConversionStrategy=RGB'
			. ' -sZUGFeRDXMLFile=%s'
			. ' -sZUGFeRDProfile=%s'
			. ' -sZUGFeRDVersion=2p1'
			. ' -sZUGFeRDConformanceLevel=MINIMUM'
			. ' -dPDFACompatibilityPolicy=1'
			. ' -o %s %s %s',
			escapeshellarg(self::ROOT . ':' . STATIC_CACHE_ROOT),
			escapeshellarg($tmp_xml_file),
			escapeshellarg(self::ROOT . '/rgb.icc'),
			escapeshellarg($path ?? '-'),
			escapeshellarg(self::ROOT . '/zugferd.gs'),
			escapeshellarg($tmp_pdf_file)
		);

		try {
			$out = Utils::quick_exec($cmd, 5);
			return $path ? null : $out;
		}
		finally {
			Static_Cache::remove($id);
			Utils::safe_unlink($tmp_pdf_file);
		}
	}
}
