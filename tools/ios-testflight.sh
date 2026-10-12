#!/bin/sh
# Archive l'app iOS et l'envoie sur TestFlight : composer ios:testflight
# --no-upload : s'arrête à l'.ipa signé dans build/ios/ (pour vérifier la signature).
# Signature et envoi passent par le compte Apple configuré dans Xcode (équipe GDFM8ML2BH).
set -e
cd "$(dirname "$0")/.."

npm run -s css
php tools/build-ios.php

# Archive non signée : l'export la signe pour l'App Store, sans exiger d'iPhone enregistré sur le compte.
# Numéro de build daté : toujours plus grand que le précédent, sans rien commiter.
BUILD=$(date +%Y%m%d%H%M)
OUT=build/ios
DESTINATION=upload
[ "$1" = "--no-upload" ] && DESTINATION=export

rm -rf "$OUT"
mkdir -p "$OUT"
cat > "$OUT/ExportOptions.plist" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>method</key><string>app-store-connect</string>
    <key>destination</key><string>$DESTINATION</string>
    <key>teamID</key><string>GDFM8ML2BH</string>
    <key>signingStyle</key><string>automatic</string>
</dict>
</plist>
PLIST

echo "Archive (build $BUILD)…"
xcodebuild -quiet -project ios/Vigilante.xcodeproj -scheme Vigilante -configuration Release \
    -destination 'generic/platform=iOS' -archivePath "$OUT/Vigilante.xcarchive" \
    -allowProvisioningUpdates CODE_SIGNING_ALLOWED=NO CURRENT_PROJECT_VERSION="$BUILD" archive

echo "Export ($DESTINATION)…"
xcodebuild -quiet -exportArchive -archivePath "$OUT/Vigilante.xcarchive" -exportPath "$OUT" \
    -exportOptionsPlist "$OUT/ExportOptions.plist" -allowProvisioningUpdates

if [ "$DESTINATION" = upload ]; then
    echo "Build $BUILD envoyé : il apparaît dans TestFlight après le traitement d'Apple (10 à 30 min)."
else
    echo "Build $BUILD signé : $OUT/Vigilante.ipa (non envoyé)."
fi
