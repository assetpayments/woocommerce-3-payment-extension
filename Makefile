all :
	if [[ -e assetpayments.zip ]]; then rm assetpayments.zip; fi
	zip -r assetpayments.zip upload install.xml