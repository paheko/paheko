KD2_FILE := https://fossil.kd2.org/kd2fw/uv/KD2-5.6.zip

deps:
	$(eval TMP_KD2=$(shell mktemp -d))
	#cd ${TMP_KD2}

	wget ${KD2_FILE} -O ${TMP_KD2}/kd2.zip

	rm -rf "include/lib/KD2"
	unzip "${TMP_KD2}/kd2.zip" -d include/lib

	rm -rf ${TMP_KD2}

dev-server:
	php -S localhost:8082 -t www www/_route.php