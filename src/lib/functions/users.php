<?php

/**
 * Manage user accounts
 * Handle user deletion and security checks.
 */
if (!defined('GRINDS_APP')) exit;

/**
 * Delete user with security checks.
 *
 * @param PDO $pdo
 * @param int $userId
 * @param int $currentUserId
 * @throws Exception
 */
function grinds_delete_user(PDO $pdo, int $userId, int $currentUserId)
{
    if ($userId === $currentUserId) {
        throw new Exception(_t('err_cannot_delete_self'));
    }

    // Prevent deleting the last administrator
    $stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtRole->execute([$userId]);
    $targetRole = $stmtRole->fetchColumn();

    if ($targetRole === 'admin') {
        $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        if ($adminCount <= 1) {
            throw new Exception(_t('err_last_admin'));
        }
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    // Invalidate active sessions for the deleted user
    $sessionDir = session_save_path() ?: ROOT_PATH . '/data/sessions';
    if (is_dir($sessionDir)) {
        $currentSessionId = session_id();
        foreach (glob($sessionDir . '/sess_*') as $file) {
            if (basename($file) !== 'sess_' . $currentSessionId) {
                $content = @file_get_contents($file);
                if ($content !== false && strpos($content, 'user_id|i:' . $userId . ';') !== false) {
                    @unlink($file);
                }
            }
        }
    }
}
