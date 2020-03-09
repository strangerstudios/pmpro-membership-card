# Change every instance of pmpro-membership-card below to match your actual plugin slug
#---------------------------
# This script generates a new pmpro.pot file for use in translations.
# To generate a new pmpro-membership-card.pot, cd to the main /pmpro-membership-card/ directory,
# then execute `languages/gettext.sh` from the command line.
# then fix the header info (helps to have the old pmpro.pot open before running script above)
# then execute `cp languages/pmpro-membership-card.pot languages/pmpro-membership-card.po` to copy the .pot to .po
# then execute `msgfmt languages/pmpro-membership-card.po --output-file languages/pmpro-membership-card.mo` to generate the .mo
#---------------------------
echo "Updating pmpro-membership-card.pot... "
xgettext -j -o languages/pmpro-membership-card.pot \
--default-domain=pmpro-membership-card \
--language=PHP \
--keyword=_ \
--keyword=__ \
--keyword=_e \
--keyword=_ex \
--keyword=_n \
--keyword=_x \
--sort-by-file \
--package-version=1.0 \
--msgid-bugs-address="info@paidmembershipspro.com" \
$(find . -name "*.php")
echo "Done!"