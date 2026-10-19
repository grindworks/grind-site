#!/bin/bash

# GrindSite Release Automation Script
# Usage: ./bin/release.sh vX.Y.Z "Release title"
#
# Before running:
#   1. Write bin/release_template.txt for this version (line 2 must start with "vX.Y.Z ").
#   2. Make sure every new file you want to ship is added with `git add`.
#
# Order matters: update.json on main is what installed sites poll, so main is pushed
# only after the GitHub Release exists and its asset hash has been verified.

set -euo pipefail

REPO="grindworks/grind-site"
TEMPLATE="bin/release_template.txt"

fail() {
  echo "❌ Error: $1" >&2
  exit 1
}

if [ $# -lt 1 ]; then
  echo "Usage: ./bin/release.sh vX.Y.Z \"Release title\""
  exit 1
fi

VERSION=$1
MESSAGE=${2:-"Release $VERSION"}
RAW_VERSION=${VERSION#v}
ZIP_FILE="grindsite-${VERSION}.zip"

cd "$(dirname "$0")/.."

# ---------------------------------------------------------------
# 0. Pre-flight checks (nothing is changed until all of them pass)
# ---------------------------------------------------------------
echo "🔎 Running pre-flight checks..."

[[ "$VERSION" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail "Version must look like v1.2.3 (got: $VERSION)."
command -v gh >/dev/null || fail "GitHub CLI (gh) is required."
gh auth status >/dev/null 2>&1 || fail "gh is not logged in. Run: gh auth login"

[ "$(git rev-parse --abbrev-ref HEAD)" = "main" ] || fail "Release from the main branch."
git fetch -q origin
[ "$(git rev-list --count HEAD..origin/main)" = "0" ] || fail "origin/main has commits you do not have. Pull first."
if git rev-parse -q --verify "refs/tags/$VERSION" >/dev/null; then fail "Tag $VERSION already exists locally."; fi
if git ls-remote --exit-code --tags origin "refs/tags/$VERSION" >/dev/null 2>&1; then fail "Tag $VERSION already exists on GitHub."; fi
[ ! -e "$ZIP_FILE" ] || fail "$ZIP_FILE already exists. Remove it first."

UNTRACKED=$(git ls-files --others --exclude-standard)
if [ -n "$UNTRACKED" ]; then
  echo "$UNTRACKED"
  fail "Untracked files found. git add the ones to ship, or add the rest to .gitignore."
fi

CURRENT_VERSION=$(sed -n "s/.*define('CMS_VERSION', '\([^']*\)');.*/\1/p" src/lib/info.php | head -1)
php -r 'exit(version_compare($argv[1], $argv[2], ">") ? 0 : 1);' "$RAW_VERSION" "$CURRENT_VERSION" \
  || fail "$VERSION is not newer than the current version $CURRENT_VERSION."

sed -n 2p "$TEMPLATE" | grep -q "^$VERSION " || fail "$TEMPLATE line 2 does not start with \"$VERSION \". Write the release notes first."

# Restore install.php only when it is missing (it deletes itself after a local install)
if [ ! -f src/install.php ]; then
  echo "🔄 Restoring auto-deleted src/install.php..."
  git restore src/install.php
fi

echo "🧪 Checking PHP syntax..."
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null || fail "PHP syntax error in $f"
done < <(find src bin tools -name '*.php' -not -path '*/vendor/*' -not -path '*/node_modules/*' -print0)

if [ -f tools/check/run_all.php ]; then
  echo "🧪 Running tools/check/run_all.php..."
  php tools/check/run_all.php >/dev/null || fail "tools/check/run_all.php failed. Run it directly to see the report."
  [ -z "$(git ls-files --others --exclude-standard)" ] || fail "Checks created untracked files."
fi

# ---------------------------------------------------------------
# 1. Bump versions and commit
# ---------------------------------------------------------------
echo "📝 Bumping versions to $RAW_VERSION..."
sed -i '' "s/define('CMS_VERSION', '[^']*');/define('CMS_VERSION', '$RAW_VERSION');/" src/lib/info.php
sed -i '' 's/"version": "[^"]*"/"version": "'"$RAW_VERSION"'"/' composer.json
sed -i '' 's/"version": "[^"]*"/"version": "'"$RAW_VERSION"'"/' package.json
sed -i '' 's/# GrindSite v.*/# GrindSite v'"$RAW_VERSION"'/' README.md
sed -i '' 's/version-[0-9\.]*-blue\.svg/version-'"$RAW_VERSION"'-blue.svg/' README.md

echo "📦 Committing changes..."
git add -A
git commit -q -m "feat: release $VERSION - $MESSAGE"

# ---------------------------------------------------------------
# 2. Build the ZIP from src/ and record its hash in update.json
# ---------------------------------------------------------------
echo "🗜️ Creating ZIP archive..."
git archive --format=zip --output="$ZIP_FILE" HEAD:src
HASH=$(shasum -a 256 "$ZIP_FILE" | awk '{print $1}')
echo "✅ $ZIP_FILE  sha256=$HASH"

echo "📝 Updating update.json..."
TODAY=$(date +%Y-%m-%d)
SAFE_MESSAGE=${MESSAGE//&/\\&}
sed -i '' 's/"version": "[^"]*"/"version": "'"$RAW_VERSION"'"/' update.json
sed -i '' 's/"release_date": "[^"]*"/"release_date": "'"$TODAY"'"/' update.json
sed -i '' 's|"message": "[^"]*"|"message": "'"$SAFE_MESSAGE"'"|' update.json
sed -i '' 's|"download_url": "[^"]*"|"download_url": "https://github.com/'"$REPO"'/releases/download/'"$VERSION"'/'"$ZIP_FILE"'"|' update.json
sed -i '' 's/"sha256": "[^"]*"/"sha256": "'"$HASH"'"/' update.json
git add update.json
git commit -q --amend --no-edit

# ---------------------------------------------------------------
# 3. Publish: tag -> GitHub Release -> verify -> main
# ---------------------------------------------------------------
echo "🏷️ Tagging and pushing $VERSION..."
git tag -a "$VERSION" -m "$MESSAGE"
git push -q origin "$VERSION"

echo "🚀 Creating GitHub Release..."
NOTES_FILE=$(mktemp)
tail -n +5 "$TEMPLATE" > "$NOTES_FILE"
gh release create "$VERSION" "$ZIP_FILE" -R "$REPO" --title "$(sed -n 2p "$TEMPLATE")" --notes-file "$NOTES_FILE" --verify-tag
rm -f "$NOTES_FILE"

echo "🔐 Verifying the uploaded asset..."
VERIFY_DIR=$(mktemp -d)
gh release download "$VERSION" -R "$REPO" -p "$ZIP_FILE" -D "$VERIFY_DIR"
REMOTE_HASH=$(shasum -a 256 "$VERIFY_DIR/$ZIP_FILE" | awk '{print $1}')
rm -rf "$VERIFY_DIR"
if [ "$REMOTE_HASH" != "$HASH" ]; then
  fail "Uploaded asset hash ($REMOTE_HASH) does not match update.json ($HASH). main was NOT pushed, so sites will not see this release. Fix the release on GitHub, then push main."
fi

echo "⬆️ Pushing main (sites can now see $VERSION)..."
git push -q origin main

rm -f "$ZIP_FILE"
echo "🎉 Released $VERSION: https://github.com/$REPO/releases/tag/$VERSION"
