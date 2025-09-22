<?php
/**
 * Système de notifications pour la plateforme éducative
 * Gère les notifications pour les messages, annonces et autres événements
 */

require_once '../config/database.php';

/**
 * Ajoute une notification pour un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @param string $titre Titre de la notification
 * @param string $contenu Contenu de la notification
 * @param string $type Type de notification (message, annonce, systeme, etc.)
 * @param int $reference_id ID de référence (optionnel, pour lier à un message, etc.)
 * @return bool True si réussite, false si échec
 */
function addNotification($user_id, $titre, $contenu, $type = 'systeme', $reference_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, titre, contenu, type, reference_id, date_creation, lu) 
            VALUES (?, ?, ?, ?, ?, NOW(), 0)
        ");
        
        return $stmt->execute([$user_id, $titre, $contenu, $type, $reference_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout de notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les notifications d'un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @param int $limit Nombre maximum de notifications à récupérer (0 pour toutes)
 * @param bool $non_lues_seulement True pour récupérer seulement les notifications non lues
 * @return array Tableau des notifications
 */
function getNotifications($user_id, $limit = 10, $non_lues_seulement = false) {
    global $pdo;
    
    try {
        $sql = "
            SELECT * FROM notifications 
            WHERE user_id = ?
        ";
        
        if ($non_lues_seulement) {
            $sql .= " AND lu = 0";
        }
        
        $sql .= " ORDER BY date_creation DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Marque une notification comme lue
 * @param int $user_id ID de l'utilisateur
 * @param int $notification_id ID de la notification
 * @return bool True si réussite, false si échec
 */
function markNotificationAsRead($user_id, $notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET lu = 1, date_lecture = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de notification comme lue: " . $e->getMessage());
        return false;
    }
}

/**
 * Marque toutes les notifications d'un utilisateur comme lues
 * @param int $user_id ID de l'utilisateur
 * @param string $type Type de notifications à marquer comme lues (optionnel)
 * @return bool True si réussite, false si échec
 */
function markAllNotificationsAsRead($user_id, $type = null) {
    global $pdo;
    
    try {
        $sql = "
            UPDATE notifications 
            SET lu = 1, date_lecture = NOW() 
            WHERE user_id = ?
        ";
        
        $params = [$user_id];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de toutes les notifications comme lues: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une notification
 * @param int $user_id ID de l'utilisateur
 * @param int $notification_id ID de la notification
 * @return bool True si réussite, false si échec
 */
function deleteNotification($user_id, $notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM notifications 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Compte le nombre de notifications non lues pour un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @return int Nombre de notifications non lues
 */
function countUnreadNotifications($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND lu = 0
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des notifications non lues: " . $e->getMessage());
        return 0;
    }
}

/**
 * Crée une notification pour un message (fonction utilitaire)
 * @param int $user_id ID de l'utilisateur
 * @param int $message_id ID du message
 * @param string $expediteur Nom de l'expéditeur
 * @param string $sujet Sujet du message
 * @return bool True si réussite, false si échec
 */
function addMessageNotification($user_id, $message_id, $expediteur, $sujet) {
    $titre = "Nouveau message de " . $expediteur;
    $contenu = "Vous avez reçu un nouveau message: " . $sujet;
    
    return addNotification($user_id, $titre, $contenu, 'message', $message_id);
}

/**
 * Crée une notification pour une annonce (fonction utilitaire)
 * @param int $user_id ID de l'utilisateur
 * @param int $annonce_id ID de l'annonce
 * @param string $titre_annonce Titre de l'annonce
 * @param string $auteur Auteur de l'annonce
 * @return bool True si réussite, false si échec
 */
function addAnnonceNotification($user_id, $annonce_id, $titre_annonce, $auteur) {
    $titre = "Nouvelle annonce de " . $auteur;
    $contenu = "Nouvelle annonce: " . $titre_annonce;
    
    return addNotification($user_id, $titre, $contenu, 'annonce', $annonce_id);
}

/**
 * Crée une notification système (fonction utilitaire)
 * @param int $user_id ID de l'utilisateur
 * @param string $titre Titre de la notification
 * @param string $contenu Contenu de la notification
 * @return bool True si réussite, false si échec
 */
function addSystemNotification($user_id, $titre, $contenu) {
    return addNotification($user_id, $titre, $contenu, 'systeme');
}