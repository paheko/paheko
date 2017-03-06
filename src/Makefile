KD2_BRANCH=dev

deps:
	$(eval TMP_KD2=$(shell mktemp -d))
	#cd ${TMP_KD2}

	wget https://fossil.kd2.org/kd2fw/zip/KD2+Framework-${KD2_BRANCH}.zip?uuid=${KD2_BRANCH} -O ${TMP_KD2}/kd2.zip
	unzip "${TMP_KD2}/kd2.zip" -d ${TMP_KD2}

	rm -rf "include/lib/KD2"
	mv "${TMP_KD2}/KD2 Framework-${KD2_BRANCH}/src/lib/kd2" "include/lib/KD2"
	rm -rf ${TMP_KD2}