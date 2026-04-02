=========================================================================
 GrindSite - Developer CLI & Tool Directory
=========================================================================

[English]
This directory contains command-line tools for developers and server administrators.
These scripts are intended for use in a local development environment or via SSH on a server.

- grind.php:
  The powerful main CLI tool for GrindSite. It allows full management of the system
  without using the web interface.
  Key features:
  - Cache management (clear page caches and temporary files)
  - Maintenance mode (toggle site access for deployments)
  - User management (list, create users, and reset passwords)
  - Database tools (optimization and search index rebuilding)
  - Backup & Recovery (create, list, and restore database snapshots)
  - Migration (generate full deployment packages)
  - Plugin & Theme management (list and toggle status)

  Usage: php bin/grind.php <command>
  For a full list of commands, run:
  $ php bin/grind.php help

- release.sh:
  An automation script for creating a new release package (ZIP).
  It handles versioning, creating a clean archive, calculating hashes, and tagging in Git.
  Usage: ./bin/release.sh <version> "<commit_message>"
  Example:
  $ ./bin/release.sh v1.2.3 "Fix some bugs and improve UI"

-------------------------------------------------------------------------

[Japanese]
このディレクトリは、開発者およびサーバー管理者向けのコマンドラインツールを格納する場所です。
ローカル開発環境や、サーバーへSSH接続して使用することを想定しています。

- grind.php:
  GrindSiteの強力なメインCLIツールです。Web画面を開くことなく、サーバー運用に必要な
  ほとんどの操作を完結させることができます。
  主な機能:
  - キャッシュ管理（ページキャッシュや一時ファイルのクリア）
  - メンテナンスモード（デプロイ時のアクセス制限の切り替え）
  - ユーザー管理（一覧表示、新規作成、パスワードリセット）
  - データベースツール（最適化、検索インデックスの再構築）
  - バックアップと復元（スナップショットの作成、一覧、CLIからの復旧）
  - 移行（データベースとアップロードファイルを含めた一括パッケージ作成）
  - プラグイン・テーマ管理（一覧表示、有効化・無効化の切り替え）

  使い方: php bin/grind.php <コマンド>
  利用可能なコマンドの一覧は、以下のコマンドで確認できます。
  $ php bin/grind.php help

- release.sh:
  新しいリリースパッケージ（ZIPファイル）を作成するための自動化スクリプトです。
  バージョン管理、クリーンなアーカイブ作成、ハッシュ値の計算、Gitのタグ打ちまでを自動で行います。
  使い方: ./bin/release.sh <バージョン> "<コミットメッセージ>"
  使用例:
  $ ./bin/release.sh v1.2.3 "いくつかのバグ修正とUI改善"
