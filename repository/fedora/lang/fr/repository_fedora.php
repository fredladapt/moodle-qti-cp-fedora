<?php

$string['configplugin'] = 'Configuration pour le plugin de téléchargement';
$string['repositorydesc'] = 'Entrepôt de données Fedora';
$string['repositoryname'] = 'Fedora';
$string['pluginname'] = 'Fedora';

$string['upload:view'] = 'Utiliser le téléchargement dans gestionnaire de fichiers.';

$string['base_url'] = 'URL de base';
$string['content_access_url'] = ' URL pour accèder au contenu';
$string['client_certificate_file'] = 'Certificat client';
$string['client_certificate_key_file'] = 'Clefs du certificat client';
$string['client_certificate_key_password'] = 'Mot de passe de la clefs client';
$string['check_target_certificate'] = 'Vérifier le certificat';
$string['target_ca_file'] = 'Fichier CA';
$string['basic_login'] = 'Identifiant';
$string['basic_password'] = 'Mot de passe';
$string['max_results'] = 'Nombre maximum de résultats';
$string['show_system_datastreams'] = 'Afficher les données systèmes';

$string['base_url_help'] = 'Entrer l\'url de base pour accéder à l\'entrepot de données. <br />Si l\'accès est sécurisé il faut impérativement spécifier une url en http<b>s</b>. Le module ne supportant pas les redirections.<p>Valeur par défault: https://(your domain)/fedora</p>';
$string['content_access_url_help'] = 'Entrer l\'url pour accéder au contenu d\'un objet. <br />Si l\'accès est sécurisé il faut impérativement spécifier une url en http<b>s</b>. Le module ne supportant pas les redirections.<p>Valeur par défault: https://(your domain)/fedora/objects/$pid/datastreams/$dsID/content</p>';
$string['api_help'] = 'Sélectionner une API pour accéder à Fedora. L\'API détermine la méthode d\'accès à Fedora. Cet à dire les requêtes disponibles, les métadonnées l\'utilisation de collections, etc. <p>Valeur par défault: UniGe</p>';
$string['client_certificate_file_help'] = 'File path to the certificate used for autentication. If not needed leave blank. <p>Default: contact your system administrator.</p>';
$string['client_certificate_key_file_help'] = 'Chemin d\'accès au fichier contenant votre clefs privée. La clefs est utilisée pour identifier votre instance de Moodle auprès de Fedora. Optionel. Laissez le champs vide si vous n\'utilisez pas cette méthode d\'accès. <p>Valeur par défault: contactez votre administrateur système.</p>';
$string['client_certificate_key_password_help'] = 'Mot de passe utilisé pour accéder à votre clefs privée. <p>Valeur par défault: contactez votre administrateur système.</p>';
$string['check_target_certificate_help'] = 'Entrez \'true\' pour vérifier si votre certificat a été fourni par fournisseur - CA - reconnu. . Entrez \'false\' ou laissez vide si vous ne voulez pas vérifier la validité de votre certificat. <p>Valeur par défault: vide.</p>';
$string['target_ca_file_help'] = 'The file path to the CA certificate used to validate your authentication certificate.<p>Default: leave blank.</p>';
$string['basic_login_help'] = 'Identifiant utilisé pour vous identifier auprès de Fedora. Normallement utilisé pour appeler l\'API M - pour les modifications.<p>Valeur par défaults: fedoraAdmin.</p>';
$string['basic_password_help'] = 'Mot de passe utilisé pour vous identifier auprès de Fedora. Normallement utilisé pour appeler l\'API M - pour les modifications. <p>Valeur par défault: contactez votre administrateur système.</p>';
$string['max_results_help'] = 'Nombre maximum de résultat retourné par Fedora. <p>Valeur par défault: 250.</p>';
$string['show_system_datastreams_help'] = 'Vraie pour affichers les données systèmes, faux dans le cas contraire. <p>Valeur par défault: faux.</p>';

$string['query'] = 'Requête';

$string['api'] = 'API';

$string['search_level'] = 'Niveau de recherche';
$string['exact'] = 'Exacte';
$string['wildcard'] = 'Joker';
$string['fuzzy'] = 'Flou';
$string['regex'] = 'Expression régulière';
$string['best_effort'] = 'Meilleur effort';

$string['hit_page_size'] = 'Nombre de résultats par page';
$string['hit_page_start'] = 'Afficher les résultats depuis l\'enregistrement no';
$string['my_stuff'] = 'Données personnelles';

$string['sort'] = 'Tri';
$string['by_relevance_ascending'] = 'Par pertinance de bas en haut';
$string['by_relevance_descending'] = 'Par pertinance de haut en bas';
$string['by_title_ascending'] = 'Par titre de bas en haut';
$string['by_title_descending'] = 'Par titre de haut en bas';
$string['by_date_ascending'] = 'Par date de bas en haut';
$string['by_date_descending'] = 'Par date de haut en bas';
$string['by_author_ascending'] = 'Par autheur de bas en haut';
$string['by_author_descending'] = 'Par autheur de haut en bas';

$string['institutions'] = 'Institutions';
$string['mystuff'] = 'Données personnelles';
$string['today'] = 'Aujourd\'hui';
$string['this_week'] = 'Cette semaine';
$string['last_week'] = 'Semaine passèe';
$string['two_weeks_ago'] = 'Il y a deux semaines';
$string['three_weeks_ago'] = 'Il y a trois semaines';
$string['history'] = 'Historique';
$string['root'] = 'Racine';
$string['lastobjects'] = 'Objets récents';












