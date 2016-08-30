KD2_BRANCH=trunk

deps:
	$(eval TMP_KD2=$(shell mktemp -d))
	cd ${TMP_KD2}

	wget https://fossil.kd2.org/kd2fw/zip/KD2+Framework-${KD2_BRANCH}.zip -O ${TMP_KD2}/kd2.zip
	unzip "${TMP_KD2}/kd2.zip" -d ${TMP_KD2}

	mv "${TMP_KD2}/KD2 Framework-${KD2_BRANCH}/src/lib/kd2" "include/lib/KD2"
	rm -rf ${TMP_KD2}