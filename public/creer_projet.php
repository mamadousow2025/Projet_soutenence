<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de Projet - Tableau de Bord</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #009688;
            --secondary-color: #FF9800;
            --light-teal: #e0f2f1;
            --light-orange: #fff3e0;
            --dark-teal: #00796b;
        }
        
        .dashboard-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background-color: var(--primary-color);
            color: white;
        }
        .stat-card-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .bg-teal { background-color: var(--primary-color); }
        .bg-orange { background-color: var(--secondary-color); }
        .bg-light-teal { background-color: var(--light-teal); }
        .bg-light-orange { background-color: var(--light-orange); }
        .btn-teal { 
            background-color: var(--primary-color); 
            border-color: var(--primary-color);
            color: white;
        }
        .btn-teal:hover {
            background-color: var(--dark-teal);
            border-color: var(--dark-teal);
        }
        .btn-orange { 
            background-color: var(--secondary-color); 
            border-color: var(--secondary-color);
            color: white;
        }
        .btn-orange:hover {
            background-color: #f57c00;
            border-color: #f57c00;
        }
        .nav-tabs .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .nav-tabs .nav-link {
            color: var(--primary-color);
        }
        .file-upload-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .file-upload-area:hover, .file-upload-area.dragover {
            border-color: var(--primary-color);
            background-color: var(--light-teal);
        }
        .file-preview {
            max-height: 150px;
            overflow-y: auto;
            margin-top: 15px;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-icon {
            font-size: 20px;
            margin-right: 10px;
            color: var(--primary-color);
        }
        .file-actions .btn {
            padding: 2px 6px;
            font-size: 12px;
        }
        .tab-content {
            background-color: #f8f9fa;
            border-radius: 0 0 10px 10px;
            padding: 20px;
        }
        .form-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--light-teal);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .feature-icon {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 10px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .progress-container {
            margin-top: 10px;
        }
        .upload-status {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center p-3 bg-teal text-white rounded">
                    <h1><i class="fas fa-project-diagram me-2"></i>Tableau de Bord - Création de Projet</h1>
                    <div>
                        <span class="me-3">Enseignant</span>
                        <a href="logout.php" class="btn btn-light btn-sm">Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Créer un Projet -->
        <div class="tab-pane fade show active" id="creer-projet">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <!-- Messages de succès/erreur -->
                    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>Projet créé avec succès !
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erreur lors de la création du projet : <?= htmlspecialchars($_GET['error']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card dashboard-card">
                        <div class="card-header bg-light-teal d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Créer un Nouveau Projet</h4>
                            <span class="badge bg-teal">Étape 1/3</span>
                        </div>
                        <div class="card-body">
                            <form id="projetForm" action="creer_projet.php" method="POST" enctype="multipart/form-data">
                                <!-- Section Informations de base -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-info-circle feature-icon"></i>Informations de base</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="titre" class="form-label">Titre du projet *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                                <input type="text" class="form-control" id="titre" name="titre" required 
                                                       placeholder="Entrez un titre clair et descriptif">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="type_projet" class="form-label">Type de projet *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-tasks"></i></span>
                                                <select class="form-select" id="type_projet" name="type_projet" required>
                                                    <option value="">Sélectionnez un type</option>
                                                    <option value="cahier_charge">Cahier des charges</option>
                                                    <option value="sujet_pratique">Sujet pratique</option>
                                                    <option value="creation">Création</option>
                                                    <option value="recherche">Projet de recherche</option>
                                                    <option value="developpement">Développement</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description détaillée *</label>
                                        <div class="input-group">
                                            <span class="input-group-text align-items-start pt-2"><i class="fas fa-align-left"></i></span>
                                            <textarea class="form-control" id="description" name="description" rows="4" 
                                                      required placeholder="Décrivez le projet en détail, ses objectifs, ses attendus..."></textarea>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-lightbulb me-1"></i>Une description claire aide les étudiants à mieux comprendre le projet.
                                        </div>
                                    </div>
                                </div>

                                <!-- Section Public cible -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-users feature-icon"></i>Public cible</h5>
                                    
                                    <div class="mb-3">
                                        <label for="filieres" class="form-label">Filières concernées *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                            <select class="form-select" id="filieres" name="filieres[]" multiple required 
                                                    size="4">
                                                <?php 
                                                // Simulation des filières - à remplacer par votre code PHP
                                                $filieres = [
                                                    ['id' => 1, 'nom' => 'Informatique'],
                                                    ['id' => 2, 'nom' => 'Génie Civil'],
                                                    ['id' => 3, 'nom' => 'Électromécanique'],
                                                    ['id' => 4, 'nom' => 'Commerce'],
                                                    ['id' => 5, 'nom' => 'Design'],
                                                ];
                                                foreach ($filieres as $filiere): ?>
                                                    <option value="<?= $filiere['id'] ?>">
                                                        <?= htmlspecialchars($filiere['nom']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle me-1"></i>Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs filières. 
                                            Les étudiants de ces filières seront automatiquement assignés au projet.
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="niveau" class="form-label">Niveau d'étude</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                            <select class="form-select" id="niveau" name="niveau">
                                                <option value="">Tous niveaux</option>
                                                <option value="debutant">Débutant</option>
                                                <option value="intermediaire">Intermédiaire</option>
                                                <option value="avance">Avancé</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section Documents et ressources -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-paperclip feature-icon"></i>Documents et ressources</h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Documents à joindre</label>
                                        <div class="file-upload-area" id="dropArea">
                                            <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-teal"></i>
                                            <h5>Glissez-déposez vos fichiers ici</h5>
                                            <p class="text-muted">ou</p>
                                            <button type="button" class="btn btn-teal" id="browseBtn">
                                                <i class="fas fa-folder-open me-2"></i>Parcourir vos fichiers
                                            </button>
                                            <input type="file" id="fileInput" multiple style="display: none;" name="documents[]">
                                            <div class="upload-status mt-2" id="uploadStatus"></div>
                                        </div>
                                        
                                        <div class="file-preview" id="filePreview">
                                            <!-- Les fichiers sélectionnés apparaîtront ici -->
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="liens" class="form-label">Liens externes</label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="fas fa-link"></i></span>
                                            <input type="url" class="form-control" id="lien1" name="liens[]" 
                                                   placeholder="https://exemple.com/ressource">
                                        </div>
                                        <div id="additionalLinks">
                                            <!-- Liens supplémentaires ajoutés dynamiquement -->
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-teal mt-2" id="addLinkBtn">
                                            <i class="fas fa-plus me-1"></i>Ajouter un autre lien
                                        </button>
                                    </div>
                                </div>

                                <!-- Section Délais et échéances -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-calendar-alt feature-icon"></i>Délais et échéances</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="date_debut" class="form-label">Date de début</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-play-circle"></i></span>
                                                <input type="date" class="form-control" id="date_debut" name="date_debut"
                                                       min="<?= date('Y-m-d') ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="date_limite" class="form-label">Date limite de rendu</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-flag-checkered"></i></span>
                                                <input type="date" class="form-control" id="date_limite" name="date_limite"
                                                       min="<?= date('Y-m-d') ?>">
                                            </div>
                                            <small class="form-text text-muted">Laisser vide si aucune date limite</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="delai_alerte" class="form-label">Alerte avant échéance</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-bell"></i></span>
                                            <select class="form-select" id="delai_alerte" name="delai_alerte">
                                                <option value="">Pas d'alerte</option>
                                                <option value="1">1 jour avant</option>
                                                <option value="3">3 jours avant</option>
                                                <option value="7">1 semaine avant</option>
                                                <option value="14">2 semaines avant</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section Paramètres avancés -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="fas fa-cogs feature-icon"></i>Paramètres avancés</h5>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="auto_assign" name="auto_assign" checked>
                                            <label class="form-check-label" for="auto_assign">
                                                Assigner automatiquement les étudiants des filières sélectionnées
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notif_creation" name="notif_creation" checked>
                                            <label class="form-check-label" for="notif_creation">
                                                Notifier les étudiants par email lors de la création du projet
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="visibilite" name="visibilite" checked>
                                            <label class="form-check-label" for="visibilite">
                                                Rendre le projet visible immédiatement
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="mot_cles" class="form-label">Mots-clés</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                            <input type="text" class="form-control" id="mot_cles" name="mot_cles" 
                                                   placeholder="Séparés par des virgules (ex: programmation, web, database)">
                                        </div>
                                        <small class="form-text text-muted">Ces mots-clés aideront à catégoriser et rechercher votre projet</small>
                                    </div>
                                </div>

                                <!-- Boutons d'action -->
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-teal btn-lg px-5">
                                        <i class="fas fa-save me-2"></i>Créer le projet
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-lg ms-2" id="previewBtn">
                                        <i class="fas fa-eye me-2"></i>Aperçu
                                    </button>
                                    <a href="projet.php" class="btn btn-secondary btn-lg ms-2">
                                        <i class="fas fa-times me-2"></i>Annuler
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de l'upload de fichiers
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('fileInput');
        const browseBtn = document.getElementById('browseBtn');
        const filePreview = document.getElementById('filePreview');
        const uploadStatus = document.getElementById('uploadStatus');
        
        // Événements pour la zone de dépôt
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('dragover');
        }
        
        function unhighlight() {
            dropArea.classList.remove('dragover');
        }
        
        // Gestion du dépôt de fichiers
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }
        
        // Gestion du bouton de parcours
        browseBtn.addEventListener('click', () => {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });
        
        // Traitement des fichiers
        function handleFiles(files) {
            if (files.length === 0) return;
            
            uploadStatus.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>Préparation de l'upload...`;
            
            // Simulation de l'upload (à remplacer par un vrai upload)
            setTimeout(() => {
                uploadStatus.innerHTML = `<i class="fas fa-check-circle me-2 text-success"></i>${files.length} fichier(s) prêt(s) à être uploadé(s)`;
                
                for (let i = 0; i < files.length; i++) {
                    addFileToPreview(files[i]);
                }
            }, 1000);
        }
        
        // Ajout d'un fichier à l'aperçu
        function addFileToPreview(file) {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            // Déterminer l'icône en fonction du type de fichier
            let fileIcon = 'fa-file';
            if (file.type.includes('image')) fileIcon = 'fa-file-image';
            else if (file.type.includes('pdf')) fileIcon = 'fa-file-pdf';
            else if (file.type.includes('word')) fileIcon = 'fa-file-word';
            else if (file.type.includes('excel')) fileIcon = 'fa-file-excel';
            else if (file.type.includes('zip')) fileIcon = 'fa-file-archive';
            
            fileItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas ${fileIcon} file-icon"></i>
                    <div>
                        <div class="fw-bold">${file.name}</div>
                        <small class="text-muted">${(file.size / 1024).toFixed(2)} KB</small>
                    </div>
                </div>
                <div class="file-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-file">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            filePreview.appendChild(fileItem);
            
            // Gestion de la suppression de fichier
            const removeBtn = fileItem.querySelector('.remove-file');
            removeBtn.addEventListener('click', function() {
                fileItem.remove();
                updateUploadStatus();
            });
        }
        
        // Mise à jour du statut d'upload
        function updateUploadStatus() {
            const fileCount = filePreview.children.length;
            if (fileCount === 0) {
                uploadStatus.innerHTML = '<i class="fas fa-info-circle me-2"></i>Aucun fichier sélectionné';
            } else {
                uploadStatus.innerHTML = `<i class="fas fa-check-circle me-2 text-success"></i>${fileCount} fichier(s) prêt(s) à être uploadé(s)`;
            }
        }
        
        // Gestion de l'ajout de liens
        const addLinkBtn = document.getElementById('addLinkBtn');
        const additionalLinks = document.getElementById('additionalLinks');
        let linkCount = 1;
        
        addLinkBtn.addEventListener('click', function() {
            linkCount++;
            const newLink = document.createElement('div');
            newLink.className = 'input-group mb-2';
            newLink.innerHTML = `
                <span class="input-group-text"><i class="fas fa-link"></i></span>
                <input type="url" class="form-control" id="lien${linkCount}" name="liens[]" 
                       placeholder="https://exemple.com/ressource">
                <button type="button" class="btn btn-outline-danger remove-link">
                    <i class="fas fa-times"></i>
                </button>
            `;
            additionalLinks.appendChild(newLink);
            
            // Gestion de la suppression de lien
            const removeBtn = newLink.querySelector('.remove-link');
            removeBtn.addEventListener('click', function() {
                newLink.remove();
            });
        });
        
        // Initialisation
        updateUploadStatus();
        
        // Gestion de l'aperçu
        const previewBtn = document.getElementById('previewBtn');
        previewBtn.addEventListener('click', function() {
            alert('Fonctionnalité d\'aperçu à implémenter');
            // Ici, vous pourriez ouvrir une modal avec un aperçu du projet
        });
    </script>
</body>
</html>