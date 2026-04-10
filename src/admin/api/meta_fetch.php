<?php

/**
 * meta_fetch.php
 *
 * Fetch Open Graph metadata from a remote URL.
 * Hardened for Enterprise: Protection against SSRF and OOM/ReDoS attacks.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_posts')) {
  json_response(['success' => false, 'error' => 'Permission denied'], 403);
}

// Validate URL
$url = $_GET['url'] ?? '';

if (!filter_var($url, FILTER_VALIDATE_URL)) {
  json_response(['success' => false, 'error' => 'Invalid URL']);
}

try {
  $options = [
    'timeout' => 10,
    'max_size' => defined('MAX_FETCH_SIZE') ? MAX_FETCH_SIZE : 2 * 1024 * 1024,
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) GrindsCMS/1.0',
    'block_private_ip' => true
  ];
  // SSRF & DNS Rebinding protection is handled inside grinds_fetch_url
  $html = grinds_fetch_url($url, $options);


  if ($html === false) {
    throw new Exception("Failed to fetch URL (Security restriction, connection error, or file too large).");
  }

  // Convert encoding safely
  $encoding = mb_detect_encoding($html, 'UTF-8, EUC-JP, SJIS, JIS', true) ?: 'UTF-8';
  if ($encoding !== 'UTF-8') {
    $html = mb_convert_encoding($html, 'UTF-8', $encoding);
  }

  if (!class_exists('DOMDocument')) {
    throw new Exception("DOMDocument extension is missing.");
  }

  // OOM & ReDoS Protection:
  // Meta tags are always located in the <head> section. Parsing multi-megabyte HTML files
  // wastes CPU and exposes the system to XML/Regex bombs. We slice the first 512KB safely.
  $htmlToParse = $html;
  if (strlen($html) > 1024 * 512) {
    $htmlToParse = mb_strcut($html, 0, 1024 * 512, 'UTF-8');
  }

  libxml_use_internal_errors(true);
  $doc = new DOMDocument();

  // Convert to HTML entities (PHP 8.2+ compatible) to prevent parsing warnings
  $htmlToParse = mb_encode_numericentity($htmlToParse, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');

  // Secure load: Disable network access and warnings
  @$doc->loadHTML($htmlToParse, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
  libxml_clear_errors();

  // Extract metadata

  $xpath = new DOMXPath($doc);

  $title = '';
  $description = '';
  $image = '';

  // Extract title
  $nodes = $xpath->query('//meta[@property="og:title"]/@content');
  if ($nodes->length > 0) $title = $nodes->item(0)->nodeValue;
  if (empty($title)) {
    $nodes = $xpath->query('//title');
    if ($nodes->length > 0) $title = $nodes->item(0)->nodeValue;
  }

  // Extract description
  $nodes = $xpath->query('//meta[@property="og:description"]/@content');
  if ($nodes->length > 0) $description = $nodes->item(0)->nodeValue;
  if (empty($description)) {
    $nodes = $xpath->query('//meta[@name="description"]/@content');
    if ($nodes->length > 0) $description = $nodes->item(0)->nodeValue;
  }

  // Extract image
  $nodes = $xpath->query('//meta[@property="og:image"]/@content');
  if ($nodes->length > 0) $image = $nodes->item(0)->nodeValue;

  // Resolve relative URL for the image
  if ($image && !preg_match('/^https?:/', $image)) {
    $parsedUrl = parse_url($url);

    if (strpos($image, '//') === 0) {
      $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'https';
      $image = $scheme . ':' . $image;
    } else {
      $base = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'localhost');
      if (substr($image, 0, 1) === '/') {
        $image = $base . $image;
      } else {
        $path = $parsedUrl['path'] ?? '/';
        if (!str_ends_with($path, '/')) {
          $path = dirname($path);
        }
        $image = $base . rtrim($path, '/\\') . '/' . ltrim($image, '/');
      }
    }
  }

  // Decode entities to ensure UTF-8 characters
  $title = html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');

  // Return metadata
  json_response([
    'success' => true,
    'data' => [
      'title' => $title,
      'description' => get_excerpt($description, 120),
      'image' => $image,
      'url' => $url,
      'host' => parse_url($url, PHP_URL_HOST)
    ]
  ]);
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
