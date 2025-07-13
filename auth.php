<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] == 'admin';
}

function isEditor() {
    return isLoggedIn() && $_SESSION['role'] == 'editor';
}

function getEditorPermissions($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT can_add, can_edit, can_delete FROM editor_permissions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $permissions = $stmt->fetch(PDO::FETCH_ASSOC);
    return $permissions ?: ['can_add' => false, 'can_edit' => false, 'can_delete' => false];
}

function canAdd($pdo) {
    if (isAdmin()) return true;
    if (isEditor()) {
        $permissions = getEditorPermissions($pdo, $_SESSION['user_id']);
        return $permissions['can_add'];
    }
    return false;
}

function canEdit($pdo) {
    if (isAdmin()) return true;
    if (isEditor()) {
        $permissions = getEditorPermissions($pdo, $_SESSION['user_id']);
        return $permissions['can_edit'];
    }
    return false;
}

function canDelete($pdo) {
    if (isAdmin()) return true;
    if (isEditor()) {
        $permissions = getEditorPermissions($pdo, $_SESSION['user_id']);
        return $permissions['can_delete'];
    }
    return false;
}
?>