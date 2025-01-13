pot:
	wp i18n make-pot . languages/parking-management.pot

po:
	wp i18n update-po languages/parking-management.pot languages/parking-management-en_US.po
	wp i18n update-po languages/parking-management.pot languages/parking-management-fr_FR.po

mo:
	wp i18n make-mo languages/parking-management-en_US.po languages/parking-management-en_US.mo
	wp i18n make-mo languages/parking-management-fr_FR.po languages/parking-management-fr_FR.mo
