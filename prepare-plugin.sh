#!/bin/bash

NEW_ABBR="eddrl_"
NEW_CLASS="EDD_Retroactive_Licensing"
NEW_FILTER="${NEW_ABBR}"
NEW_KB_PATH="20133716-Easy-Digital-Downloads-Retroactive-Licensing"
NEW_SITE="http://aihr.us/easy-digital-downloads-retroactive-licensing/"
NEW_SLUG="edd-retroactive-licensing"
NEW_TITLE="EDD Retroactive Licensing"

OLD_ABBR="wps_"
OLD_CLASS="WordPress_Starter"
OLD_FILTER="wordpress_starter"
OLD_KB_PATH="20102742-WordPress-Starter-Plugin"
OLD_SITE="http://wordpress.org/extend/plugins/wordpress-starter/"
OLD_SLUG="wordpress-starter"
OLD_TITLE="WordPress Starter"

echo
echo "Begin converting ${OLD_TITLE} to ${NEW_TITLE} plugin"

FILES=`find . -type f \( -name "*.md" -o -name "*.php" -o -name "*.txt" -o -name "*.xml" \)`
for FILE in ${FILES} 
do
	if [[ '' != ${NEW_ABBR} ]]
	then
		perl -pi -e "s#${OLD_ABBR}#${NEW_ABBR}#g" ${FILE}
	fi

	if [[ '' != ${NEW_CLASS} ]]
	then
		perl -pi -e "s#${OLD_CLASS}#${NEW_CLASS}#g" ${FILE}
	fi

	if [[ '' != ${NEW_FILTER} ]]
	then
		perl -pi -e "s#${OLD_FILTER}#${NEW_FILTER}#g" ${FILE}
	fi

	if [[ '' != ${NEW_KB_PATH} ]]
	then
		perl -pi -e "s#${OLD_KB_PATH}#${NEW_KB_PATH}#g" ${FILE}
	fi

	if [[ '' != ${NEW_SITE} ]]
	then
		perl -pi -e "s#${OLD_SITE}#${NEW_SITE}#g" ${FILE}
	fi

	if [[ '' != ${NEW_SLUG} ]]
	then
		perl -pi -e "s#${OLD_SLUG}#${NEW_SLUG}#g" ${FILE}
	fi

	if [[ '' != ${NEW_TITLE} ]]
	then
		perl -pi -e "s#${OLD_TITLE}#${NEW_TITLE}#g" ${FILE}
	fi
done

if [[ -e 000-code-qa.txt ]]
then
	rm 000-code-qa.txt
fi

mv ${OLD_SLUG}.css ${NEW_SLUG}.css
mv ${OLD_SLUG}.php ${NEW_SLUG}.php
mv languages/${OLD_SLUG}.pot languages/${NEW_SLUG}.pot
mv lib/class-${OLD_SLUG}-settings.php lib/class-${NEW_SLUG}-settings.php

exit

if [[ -e .git ]]
then
	rm -rf .git
fi

git init
git add *
git add .gitignore
git add .travis.yml
git commit -m "Initial plugin creation"

# rm ${0}