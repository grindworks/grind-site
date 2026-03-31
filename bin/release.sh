#!/bin/bash

# GrindSite Release Automation Script
# Usage: ./bin/release.sh v1.2.2 "Added powerful CLI tool and new admin skins"

if [ -z "$1" ]; then
  echo "❌ Error: Version tag is required."
  echo "Usage: ./bin/release.sh v1.2.2 \"Release message\""
  exit 1
fi

VERSION=$1
MESSAGE=${2:-"Release $VERSION"}
ZIP_FILE="grindsite-${VERSION}.zip"

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

# 4. Update update.json with new hash (macOS sed syntax)
echo "📝 Updating update.json..."
sed -i '' 's/"sha256": "[^"]*"/"sha256": "'"$HASH"'"/' update.json

# 5. Amend commit with update.json changes (keeping history clean)
echo "🔗 Amending commit..."
git add update.json
git commit --amend --no-edit

# 6. Tag and Push
echo "🏷️ Tagging and pushing to GitHub..."
git tag -a "$VERSION" -m "$MESSAGE"
git push origin main
git push origin "$VERSION"

echo "🎉 All Done! Release $VERSION has been cleanly archived, committed, tagged, and pushed."
