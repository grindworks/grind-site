#!/bin/bash

# GrindSite Release Automation Script
# Usage: ./bin/release.sh v1.6.4 "Update mail settings, translations and theme layouts"

if [ -z "$1" ]; then
  echo "❌ Error: Version tag is required."
  echo "Usage: ./bin/release.sh v1.6.4 \"Release message\""
  exit 1
fi

VERSION=$1
MESSAGE=${2:-"Release $VERSION"}
ZIP_FILE="grindsite-${VERSION}.zip"

# 0. Restore install.php if it was auto-deleted during local testing
echo "🔄 Ensuring install.php is present..."
git restore src/install.php 2>/dev/null || true

RAW_VERSION=${VERSION#v}

# 0.5 Auto-bump versions in core files BEFORE committing and zipping
echo "📝 Auto-bumping versions in core files to $RAW_VERSION..."
sed -i '' "s/define('CMS_VERSION', '[^']*');/define('CMS_VERSION', '$RAW_VERSION');/" src/lib/info.php
sed -i '' 's/"version": "[^"]*"/"version": "'"$RAW_VERSION"'"/' composer.json
sed -i '' 's/"version": "[^"]*"/"version": "'"$RAW_VERSION"'"/' package.json
sed -i '' 's/# GrindSite v.*/# GrindSite v'"$RAW_VERSION"'/' README.md
sed -i '' 's/version-[0-9\.]*-blue\.svg/version-'"$RAW_VERSION"'-blue.svg/' README.md
sed -i '' 's/Usage: \.\/bin\/release\.sh v[0-9\.]*/Usage: \.\/bin\/release\.sh '"$VERSION"'/' bin/release.sh

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

# 4. Update update.json with Hash and release info
echo "📝 Updating update.json..."
TODAY=$(date +%Y-%m-%d)
sed -i '' 's/"version": "[^"]*"/"version": "'"$RAW_VERSION"'"/' update.json
sed -i '' 's/"release_date": "[^"]*"/"release_date": "'"$TODAY"'"/' update.json
SAFE_MESSAGE=${MESSAGE//&/\\&}
sed -i '' 's|"message": "[^"]*"|"message": "'"$SAFE_MESSAGE"'"|' update.json
sed -i '' 's|"download_url": "[^"]*"|"download_url": "https://github.com/grindworks/grind-site/releases/download/'"$VERSION"'/grindsite-'"$VERSION"'.zip"|' update.json
sed -i '' 's/"sha256": "[^"]*"/"sha256": "'"$HASH"'"/' update.json

# 5. Amend commit with update.json changes (keeping history clean)
echo "🔗 Amending commit..."
git add update.json
git commit --amend --no-edit

# 6. Tag and Push
echo "🏷️ Tagging and pushing to GitHub..."
git tag -a "$VERSION" -m "$MESSAGE"
git push origin main || { echo "❌ Error: Failed to push to GitHub (Authentication or Network issue)."; exit 1; }
git push origin "$VERSION" || { echo "❌ Error: Failed to push tag."; exit 1; }

# 7. Copy release notes to clipboard (macOS / Linux with xclip)
if command -v pbcopy &> /dev/null; then
  cat bin/release_template.txt | pbcopy
  echo "📋 Release notes (bin/release_template.txt) copied to clipboard! Paste it in the GitHub Release page."
fi

echo "🎉 All Done! Release $VERSION has been cleanly archived, committed, tagged, and pushed."
