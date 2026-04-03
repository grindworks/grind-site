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

    // Invalidate active sessions for the deleted user.
    grinds_invalidate_user_sessions($userId);
}

/**
 * Invalidate all active sessions for a given user, except for the current session.
 *
 * @param int $userId The ID of the user whose sessions should be invalidated.
 * @param string|null $currentSessionId The ID of the current session, if any. If null, session_id() will be used.
 * @return void
 */
if (!function_exists('grinds_invalidate_user_sessions')) {
    function grinds_invalidate_user_sessions(int $userId, ?string $currentSessionId = null)
    {
        $sessionDir = session_save_path() ?: ROOT_PATH . '/data/sessions';
        if (is_dir($sessionDir)) {
            $currentSessionId = $currentSessionId ?: session_id();
            foreach (glob($sessionDir . '/sess_*') as $file) {
                if (basename($file) !== 'sess_' . $currentSessionId) {
                    $content = @file_get_contents($file);
                    if ($content !== false && preg_match('/user_id\|(?:i:' . $userId . ';|s:\d+:"' . $userId . '";)/', $content)) {
                        @unlink($file);
                    }
                }
            }
        }
    }
}
