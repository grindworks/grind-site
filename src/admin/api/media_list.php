<?php

/**
 * media_list.php
 *
 * API endpoint to retrieve a paginated and filtered list of media files.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_media')) {
  json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

try {
  // Get pagination parameters
  $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  if ($page < 1)
    $page = 1;

  $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
  if ($limit < 1)
    $limit = 20;
  if ($limit > 100)
    $limit = 100;

  $offset = ($page - 1) * $limit;

  // Get filter parameters
  $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
  $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
  $type = isset($_GET['type']) ? $_GET['type'] : 'all';
  $date = isset($_GET['date']) ? $_GET['date'] : '';
  $ext = isset($_GET['ext']) ? $_GET['ext'] : '';
  $tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';

  $whereConditions = [];
  $params = [];

  // Add keyword filter
  if ($keyword !== '') {
    $cond = FileManager::getSearchCondition($keyword, $params);
    if ($cond)
      $whereConditions[] = $cond;
  }

  // Add type filter
  if ($type === 'image') {
    $whereConditions[] = "file_type LIKE 'image/%'";
  } elseif ($type === 'video') {
    $whereConditions[] = "file_type LIKE 'video/%'";
  } elseif ($type === 'audio') {
    $whereConditions[] = "file_type LIKE 'audio/%'";
  } elseif ($type === 'document') {
    $whereConditions[] = "file_type NOT LIKE 'image/%' AND file_type NOT LIKE 'video/%' AND file_type NOT LIKE 'audio/%'";
  }

  // Add extension filter
  if ($ext !== '') {
    if (strtolower($ext) === 'jpg' || strtolower($ext) === 'jpeg') {
      $whereConditions[] = "(filename LIKE ? OR filename LIKE ?)";
      $params[] = "%.jpg";
      $params[] = "%.jpeg";
    } else {
      $whereConditions[] = "filename LIKE ?";
      $params[] = "%." . $ext;
    }
  }

  // Add date filter
  if ($date !== '' && preg_match('/^\d{4}-\d{2}$/', $date)) {
    $startDate = $date . '-01 00:00:00';
    $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +1 month'));

    $whereConditions[] = "uploaded_at >= ? AND uploaded_at < ?";
    $params[] = $startDate;
    $params[] = $endDate;
  }

  // Add tag filter
  if ($tag !== '') {
    $whereConditions[] = "EXISTS (SELECT 1 FROM media_tags mt JOIN tags t ON mt.tag_id = t.id WHERE mt.media_id = media.id AND t.name = ?)";
    $params[] = $tag;
  }


  $whereClause = "";
  if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(' AND ', $whereConditions);
  }

  // Set sort order
  $orderClause = "ORDER BY id DESC";
  switch ($sort) {
    case 'oldest':
      $orderClause = "ORDER BY id ASC";
      break;
    case 'name_asc':
      $orderClause = "ORDER BY filename ASC";
      break;
    case 'name_desc':
      $orderClause = "ORDER BY filename DESC";
      break;
    case 'newest':
    default:
      $orderClause = "ORDER BY id DESC";
      break;
  }

  // Get total count
  $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM media $whereClause");
  $stmtCount->execute($params);
  $total = $stmtCount->fetchColumn();

  // Fetch media files
  $sql = "SELECT * FROM media $whereClause $orderClause LIMIT ? OFFSET ?";
  $stmt = $pdo->prepare($sql);

  $i = 1;
  foreach ($params as $val) {
    $stmt->bindValue($i++, $val);
  }
  $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
  $stmt->bindValue($i++, $offset, PDO::PARAM_INT);

  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $files = [];
  foreach ($rows as $row) {
    $meta = [];
    if (isset($row['metadata']) && is_string($row['metadata'])) {
      $decoded = json_decode($row['metadata'], true);
      if (is_array($decoded))
        $meta = $decoded;
    }

    $isImage = (strpos($row['file_type'] ?? '', 'image/') === 0);

    $thumbnailUrl = null;
    if ($isImage && !empty($meta['thumbnail'])) {
      $thumbnailUrl = resolve_url($meta['thumbnail']);
    }

    $files[] = [
      'id' => $row['id'],
      'url' => resolve_url($row['filepath']),
      'thumbnail_url' => $thumbnailUrl,
      'filePath' => $row['filepath'],
      'filename' => $row['filename'],
      'file_type' => $row['file_type'],
      'file_size' => $row['file_size'],
      'is_image' => $isImage,
      'metadata' => $meta,
    ];
  }

  // Return response
  json_response([
    'success' => true,
    'files' => $files,
    'has_more' => ($offset + $limit) < $total,
    'total' => (int)$total,
    'current_page' => $page,
    'limit' => $limit
  ]);
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
