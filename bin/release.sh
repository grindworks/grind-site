#!/bin/bash

# GrindSite Release Automation Script
# Usage: ./bin/release.sh v1.5.0 "Added powerful CLI tool and new admin skins"

if [ -z "$1" ]; then
  echo "❌ Error: Version tag is required."
  echo "Usage: ./bin/release.sh v1.5.0 \"Release message\""
  exit 1
fi

VERSION=$1
MESSAGE=${2:-"Release $VERSION"}
ZIP_FILE="grindsite-${VERSION}.zip"

# 0. Restore install.php if it was auto-deleted during local testing
echo "🔄 Ensuring install.php is present..."
git restore src/install.php 2>/dev/null || true

# 1. Commit current changes
echo "📦 Committing changes..."
git add .
git commit -m "feat: release $VERSION - $MESSAGE"

# 2. Create clean ZIP from src/ directory using git archive
echo "🗜️ Creating clean ZIP archive..."
git archive --format=zip --output="$ZIP_FILE" HEAD:src
echo "✅ Created: $ZIP_FILE"

# 3. Calculate SHA256 Hash
HASH=$(shasum -a 256 "$ZIP_FILE" | awk '{print $1}')
echo "✅ Calculated Hash: $HASH"

# 4. Update update.json with new info (macOS sed syntax)
echo "📝 Updating update.json..."
RAW_VERSION=${VERSION#v}
TODAY=$(date +%Y-%m-%d)
sed -i '' 's/"version": "[^"]*"/"version": "'"$RAW_VERSION"'"/' update.json
sed -i '' 's/"release_date": "[^"]*"/"release_date": "'"$TODAY"'"/' update.json
sed -i '' 's/"message": "[^"]*"/"message": "'"$MESSAGE"'"/' update.json
sed -i '' 's|"download_url": "[^"]*"|"download_url": "https://github.com/grindworks/grind-site/releases/download/'"$VERSION"'/grindsite-'"$VERSION"'\.zip"|' update.json
sed -i '' 's/"sha256": "[^"]*"/"sha256": "'"$HASH"'"/' update.json

# Update package.json version
echo "📝 Updating package.json..."
sed -i '' 's/"version": "[^"]*"/"version": "'"$RAW_VERSION"'"/' package.json

# 5. Amend commit with update.json and package.json changes (keeping history clean)
echo "🔗 Amending commit..."
git add update.json
git add package.json
git commit --amend --no-edit

# 6. Tag and Push
echo "🏷️ Tagging and pushing to GitHub..."
git tag -a "$VERSION" -m "$MESSAGE"
git push origin main
git push origin "$VERSION"

# 7. Prepare Release Notes Template to Clipboard (macOS)
TEMPLATE_FILE="bin/release_template.txt"
if [ -f "$TEMPLATE_FILE" ]; then
  echo "📋 Copying release template to clipboard..."
  sed "s/{{VERSION}}/$VERSION/g" "$TEMPLATE_FILE" | pbcopy
  echo "✅ Template copied! You can now just Paste (Cmd+V) into GitHub."
  echo "👉 Open: https://github.com/grindworks/grind-site/releases/new?tag=$VERSION"
fi

echo "🎉 All Done! Release $VERSION has been cleanly archived, committed, tagged, and pushed."
