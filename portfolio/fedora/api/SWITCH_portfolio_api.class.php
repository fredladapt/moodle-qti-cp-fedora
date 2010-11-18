<?php

/**
 * Implementation of the SWITCH API.
 * Possible values for some of the metadata fields are hardcoded in this file since they are not provided dynamically by SWITCH.
 * For example disciplines, access rights, etc. Those values are not expected to change but modification of this file would be required if it becomes the case.
 *
 * See https://collection.switch.ch/spec/ for a list of possible values.
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class SWITCH_portfolio_api extends api_base{

	public static function get_name(){
		return 'SWITCH';
	}

	public function export_config_form(&$mform) {
		$size = 50;

		$mform->addElement('header','content_header', get_string('content', 'portfolio_fedora'));
		$mform->addElement('text', 'title', get_string('title', 'portfolio_fedora'), array('size' =>$size));
		$title = $this->get_default_title();
		$mform->setDefault('title', $title);
		$mform->addRule('title', get_string('field_required', 'portfolio_fedora'), 'required');

		$mform->addElement('textarea', 'description', get_string('summary', 'portfolio_fedora'), array('cols' =>$size-2, 'rows'=>3));

		$mform->addElement('header', 'collaborator_header', get_string('collaborator', 'portfolio_fedora'));

		$mform->addElement('text', 'creator', get_string('author', 'portfolio_fedora'), array('size' =>$size));
		global $USER;
		$mform->setDefault('creator', $USER->firstname . ' ' . $USER->lastname);

		$mform->addElement('text', 'owner', get_string('owner', 'portfolio_fedora'), array('size' =>$size, 'readonly'=>'readonly'));
		$mform->setDefault('owner', $this->get_owner());
		$mform->addRule('owner', get_string('field_required', 'portfolio_fedora'), 'required');

		$mform->addElement('header', 'classification_header', get_string('classification', 'portfolio_fedora'));

		$disciplines = $this->get_disciplines();
		$mform->addElement('text', 'discipline_text', get_string('discipline', 'portfolio_fedora'),  array('size' =>$size, 'readonly'=>'readonly'));
		$mform->addElement('hidden', 'discipline', get_string('discipline', 'portfolio_fedora'),  array('size' =>$size, 'readonly'=>'readonly'));
		$mform->setDefault('discipline', 5889); //others
		$mform->setDefault('discipline_text', $disciplines['5889']['title']); //others
		$mform->addRule('discipline_text', get_string('field_required', 'portfolio_fedora'), 'required');
		$mform->addElement('static', '', '', $this->get_discipline_tree('discipline', 'discipline_text'));

		$mform->addElement('text', 'collection_title', get_string('collections', 'portfolio_fedora'), array('size' =>$size, 'readonly'=>'readonly'));
		$mform->addElement('hidden', 'collection_id', get_string('collections', 'portfolio_fedora'), array('size' =>$size, 'readonly'=>'readonly'));

		if($default_collection = $this->get_collection_default()){
			$mform->setDefault('collection_title', $default_collection['title']);
			$mform->setDefault('collection_id', $default_collection['id']);
		}

		$mform->addRule('collection_title', get_string('field_required', 'portfolio_fedora'), 'required');
		$mform->addElement('static', '', '', $this->get_collection_tree('collection_id', 'collection_title'));

		$mform->addElement('header', 'rights_header', get_string('rights', 'portfolio_fedora'));

		$licences = $this->get_licences();
		$mform->addElement('select', 'license', get_string('license', 'portfolio_fedora'), $licences);
		$mform->setDefault('license', 'http://creativecommons.org/licenses/by-nc-sa/2.5/ch/');
		$mform->addRule('license', get_string('field_required', 'portfolio_fedora'), 'required');

		$access_rights = $this->get_access_rights();
		$mform->addElement('select', 'rights', get_string('rights', 'portfolio_fedora'), $access_rights);
		$mform->setDefault('rights', 'private');
		$mform->addRule('rights', get_string('field_required', 'portfolio_fedora'), 'required');

		$access_rights = $this->get_access_rights();
		$mform->addElement('select', 'accessRights', get_string('accessRights', 'portfolio_fedora'), $access_rights);
		$mform->setDefault('accessRights', 'private');
		$mform->addRule('accessRights', get_string('field_required', 'portfolio_fedora'), 'required');
	}

	/**
	 * Returns the default collection. Returns the institution name and id based on the user email address.
	 */
	protected function get_collection_default(){
		global $USER;
		$school = '';
		$parts = explode('@', $USER->email);
		$school = count($parts)>1 ? $parts[1] : '';
		$school = reset(explode('.', $school));
		$school = strtolower($school);
		switch($school){
			case 'unige':
				$school = 'LOR:49';
				break;
			case 'unil':
				$school = 'LOR:51';
				break;
			case 'unifr':
				$school = 'LOR:39';
				break;
			case 'uzh':
				$school = 'LOR:39';
				break;
			case 'unisg':
				$school = 'LOR:41';
				break;
			case 'unilu':
				$school = 'LOR:324';
				break;
			case 'unibe':
				$school = 'LOR:43';
				break;
			case 'unibas':
				$school = 'LOR:47';
				break;
			default:
				return false;
		}
		$collections = $this->get_collections();
		if(isset($collections[$school]) ){
			$result['id'] = $school;
			$result['title'] = $collections[$school]['title'];
		}else{
			$result['id'] = '';
			$result['title'] = '';

		}
		return $result;
	}

	public function get_export_summary(){
		$items = $this->get_allowed_export_config();
		$config = array();
		foreach($items as $item){
			if(isset($_POST[$item])){
				$config[$item] = $_POST[$item];
			}
		}
		$config['collections'] = isset($_POST['collection_id']) ? array($_POST['collection_id']) : array();
		$existing_pid = $this->get_object_by_label($config['title'], $config['owner']);
		if($existing_pid){
			$config['pid'] = $existing_pid;
		}
		$portfolio = $this->get_portfolio();
		$portfolio->set_export_config($config);

		//changes from ids to human readable text
		$config['discipline'] = isset($_POST['discipline_text']) ? $_POST['discipline_text'] :'';
		$config['collections'] = isset($_POST['collection_title']) ? array($_POST['collection_title']) : array();
		$licenses = $this->get_licences();
		$config['license'] = isset($_POST['license']) ? $licenses[$_POST['license']] :'';
		$rights = $this->get_access_rights();
		$config['accessRights'] = isset($_POST['accessRights']) ? $rights[$_POST['accessRights']] :'';
		$config['rights'] = isset($_POST['rights']) ? $rights[$_POST['rights']] :'';

		$result = array();

		$result[get_string('file_exists', 'portfolio_fedora')] = $existing_pid ? '<b>'.get_string('true', 'portfolio_fedora').'</b> ' : get_string('false', 'portfolio_fedora');

		foreach($config as $key=>$value){
			if($key != 'pid'){
				$result[get_string($key, 'portfolio_fedora')] = is_array($value) ? implode(', ', $value) : $value;
			}
		}

		$result = empty($result) ? false : $result;
		return $result;
	}

	public function content_to_foxml($content, $meta, $export_config){
		$switch = new switch_object_meta();
		$keys = $this->get_allowed_export_config();
		foreach($keys as $key){
			if(isset($export_config[$key])){
				$switch->{$key} = $export_config[$key];
			}
		}
		$source = $this->get_config('content_access_url');
		$source = $source ? $source : rtrim($this->get_config('base_url'), '/') . '/objects/$pid/datastreams/$dsID/content';
		$source = str_ireplace('$dsid', 'DS1', $source);
		$source = str_ireplace('$pid', $meta->pid, $source);
		$switch->source = $source;
		return SWITCH_content_to_foxml($content, $meta, $switch);
	}

	public function get_allowed_export_config(){
		$result = array();
		$switch = new SWITCH_object_meta();
		foreach($switch as $key=>$value){
			$result[$key] = $key;
		}
		$meta = new fedora_object_meta();
		foreach($meta as $key=>$value){
			$result[$key] = $key;
		}
		return $result;
	}

	public function send_package() {

		$portfolio = $this->get_portfolio();
		$fedora = $this->get_fedora();
		$exportconfig = $portfolio->get('exportconfig');
		$isnew = !isset($exportconfig['pid']);

		$files = $portfolio->get('exporter')->get_tempfiles();
		$file = reset($files);
		$meta = new fedora_object_meta();
		$meta->pid = $isnew ? $fedora->get_nextPID() : $exportconfig['pid'];
		$meta->label = $exportconfig['title'] ;
		$meta->mime = $file->get_mimetype();
		$meta->owner = $exportconfig['owner'];


		$switch = new switch_object_meta();
		$keys = $this->get_allowed_export_config();
		foreach($keys as $key){
			if(isset($exportconfig[$key])){
				$switch->{$key} = $exportconfig[$key];
			}
		}

		$source = $this->get_config('content_access_url');
		$source = $source ? $source : rtrim($this->get_config('base_url'), '/') . '/objects/$pid/datastreams/$dsID/content';
		$source = str_ireplace('$dsid', 'DS1', $source);
		$source = str_ireplace('$pid', $meta->pid, $source);
		$switch->source = $source;

		$content = $file->get_content();
		if(!$isnew){
			$this->update_repository_object($content, $meta, $switch);
		}else{
			$foxml = $this->content_to_foxml($content, $meta, $switch);
			$fedora->ingest($foxml, $meta->pid, $meta->label, $meta->owner);
		}

		return true;
	}

	private $_collections = array();
	protected function get_collections(){
		if($this->_collections){
			return $this->_collections;
		}

		try{
			//@todo: not very good URL should not be hardcoded
			$fedora = new FedoraProxy();
			$fedora->get_config()->set_base_url('https://collection.switch.ch/LOREST');
			$collections = $fedora->SWITCH_collections();
			$this->_collections = array();
			foreach($collections as $collection){
				$this->_collections[$collection['pid']] = $collection;
			}
			$collections = $this->_collections;

			foreach($collections as $collection){
				$parent = $collection['parent'];
				$pid = $collection['pid'];
				$title = $collection['title'];
				$collections[$parent]['children'][$pid] = $title;
			}

			foreach($collections as &$collection){
				if(isset($collection['children'])){
					$children = $collection['children'];
					asort($children);
					$collection['children'] = $children;
				}else{
					$collection['children'] = array();
				}
			}

			return $this->_collections = $collections;

		}catch(Exception $e){
			return $this->_collections = array();
		}
	}

	protected function get_collection_tree($id_el_name, $title_el_name){
		try{
			$collections = $this->get_collections();
			$root = array();
			foreach($collections as $collection){
				if(!isset($collection['parent']) || empty($collection['parent'])){
					$root = $collection;
					break;
				}
			}

			$current_level = 0;

			global $CFG;
			$result = '<script type="text/javascript"';
			$result .=  'src="'. $CFG->wwwroot .'/portfolio/fedora/resource/tree.js">';
			$result .= '</script>';
			$result .= '<ul style="list-style-type:square;">';
			$children = isset($root['children']) ? $root['children'] : array();
			$children = empty($children) ? array() : $children;
			foreach($children as $child_id => $title){
				$collection = $collections[$child_id];
				$result .= $this->to_li($collections, $collection, $id_el_name, $title_el_name);
			}

			$result .= '</ul>';
			return $result;
		}catch(Exception $e){
			return '';
		}
	}

	protected function to_li($collections, $collection, $id_el_name, $title_el_name, $image = 'folder_closed.png'){
		global $CFG;
		$result = '<li style="cursor:pointer; list-style-type:square; list-style-image:url(\''. $CFG->wwwroot . '/portfolio/fedora/resource/'.$image.'\'); "';
		$result .= 'onclick="return tree_onclick(event, this);">';
		$onclick = "return select_collection(event, this,'{$collection['pid']}', '{$collection['title']}', '{$id_el_name}', '{$title_el_name}');";
		$result .= '<a onclick="'.$onclick.'" href="#">'. $collection['title'] .'</a>' ;
		$children = isset($collection['children']) ? $collection['children'] : array();
		if(!empty($children)){

			$result .= '<ul style="display:none; list-style-type:square; margin-top:0px; margin-bottom:0px;">';

			foreach($children as $child_id => $title){
				$collection = $collections[$child_id];
				$result .= $this->to_li($collections, $collection, $id_el_name, $title_el_name, $image);
			}
			$result .= '</ul>';
		}
		$result .= '</li>';
		return $result;
	}

	private $_licences = array();
	protected function get_licences(){
		if($this->_licences){
			return $this->_licences;
		}

		$this->_licences['﻿http://creativecommons.org/licenses/by/2.5/ch/'] = get_string('switch_license_ch', 'portfolio_fedora');
		$this->_licences['http://creativecommons.org/licenses/by-nc/2.5/ch/'] = get_string('switch_license_nc', 'portfolio_fedora');
		$this->_licences['http://creativecommons.org/licenses/by-nc-nd/2.5/ch/'] = get_string('switch_license_nc_nd', 'portfolio_fedora');
		$this->_licences['http://creativecommons.org/licenses/by-nc-sa/2.5/ch/'] = get_string('switch_license_nc_sa', 'portfolio_fedora');
		$this->_licences['http://creativecommons.org/licenses/by-nd/2.5/ch/'] = get_string('switch_license_nd', 'portfolio_fedora');
		$this->_licences['http://creativecommons.org/licenses/by-sa/2.5/ch/'] = get_string('switch_license_sa', 'portfolio_fedora');
		$this->_licences[''] = get_string('switch_license_content_defined', 'portfolio_fedora');
		return $this->_licences;
	}

	protected function get_discipline($id, $level){
		$header = $level==1 ? '':  ''. str_repeat('...', $level-1) . '';
		$result = $header . get_string('switch_discipline_' . $id, 'portfolio_fedora');
		return $result;
	}

	private $_access_rights = array();
	protected function get_access_rights(){
		if($this->_access_rights){
			return $this->_access_rights;
		}

		$this->_access_rights['public'] = get_string('public', 'portfolio_fedora');
		$this->_access_rights['private'] = get_string('private', 'portfolio_fedora');
		//$this->_access_rights['federation'] = get_string('federation', 'portfolio_fedora');
		$this->_access_rights['institution'] = get_string('institution', 'portfolio_fedora');
		return $this->_access_rights;
	}

	protected function get_discipline_tree($id_el_name, $title_el_name){
		try{
			$disciplines = $this->get_disciplines();
			$root = array();
			foreach($disciplines as $key => $discipline){
				if($key=='0'){
					$root = $discipline;
					break;
				}
			}

			$current_level = 0;

			global $CFG;
			$result = '<script type="text/javascript"';
			$result .=  'src="'. $CFG->wwwroot .'/portfolio/fedora/resource/tree.js">';
			$result .= '</script>';
			$result .= '<ul style="list-style-type:square;">';
			$children = isset($root['children']) ? $root['children'] : array();
			$children = empty($children) ? array() : $children;
			foreach($children as $child_id => $child_title){
				$discipline = $disciplines[$child_id];
				$result .= $this->to_li($disciplines, $discipline, $id_el_name, $title_el_name, 'discipline.png');
			}

			$result .= '</ul>';
			return $result;
		}catch(Exception $e){
			return '';
		}
	}

	protected function get_disciplines(){
		$disciplines = array(
		  '﻿' => array('parent'=>'0','order'=>'1','level'=>'1','de'=>'Nicht definiert','en'=>'Not defined','fr'=>'Pas definie','it'=>'Non definato', 'shis'=>''),
		  '1932' => array('parent'=>'0','order'=>'1','level'=>'1','de'=>'Kunst & Kultur','en'=>'Arts & Culture','fr'=>'Arts & Culture','it'=>'Arte & Cultura', 'shis'=>''),
		  '5314' => array('parent'=>'1932','order'=>'2','level'=>'2','de'=>'Architektur','en'=>'Architecture','fr'=>'Architecture','it'=>'Architettura', 'shis'=>'7300'),
		  '5575' => array('parent'=>'5314','order'=>'3','level'=>'3','de'=>'Raumplanung','en'=>'Spatial planning','fr'=>'Aménagement du territoire','it'=>'Pianificazione del territorio', 'shis'=>''),
		  '6302' => array('parent'=>'5314','order'=>'4','level'=>'3','de'=>'Landschaftsarchitektur','en'=>'Landscape architecture','fr'=>'architecture paysagère','it'=>'Architettura del paesaggio', 'shis'=>''),
		  '9202' => array('parent'=>'1932','order'=>'5','level'=>'2','de'=>'Kunstgeschichte ','en'=>'Art history','fr'=>'Histoire de l\'art','it'=>'Storia dell\'arte', 'shis'=>'1700'),
		  '8202' => array('parent'=>'1932','order'=>'6','level'=>'2','de'=>'Musik','en'=>'Music','fr'=>'Musique','it'=>'Musica', 'shis'=>'1800'),
		  '2043' => array('parent'=>'8202','order'=>'7','level'=>'3','de'=>'Musikpädagogik','en'=>'Music education','fr'=>'Pédagogie musicale','it'=>'Educazione musicale', 'shis'=>''),
		  '9610' => array('parent'=>'8202','order'=>'8','level'=>'3','de'=>'Schul- und Kirchenmusik','en'=>'School and church music','fr'=>'musique scolaire et sacrée','it'=>'Didattica musicale e musica sacra', 'shis'=>''),
		  '3829' => array('parent'=>'1932','order'=>'9','level'=>'2','de'=>'Theater','en'=>'Theatre','fr'=>'Théatre','it'=>'Teatro', 'shis'=>'1850'),
		  '5395' => array('parent'=>'1932','order'=>'10','level'=>'2','de'=>'Film','en'=>'Film','fr'=>'Cinéma','it'=>'Cinema', 'shis'=>'1850'),
		  '1497' => array('parent'=>'1932','order'=>'11','level'=>'2','de'=>'Bildende Kunst','en'=>'Visual arts','fr'=>'Arts plastiques','it'=>'Arti Visive', 'shis'=>''),
		  '3119' => array('parent'=>'1932','order'=>'12','level'=>'2','de'=>'Design','en'=>'Design','fr'=>'Design','it'=>'Design', 'shis'=>''),
		  '5103' => array('parent'=>'3119','order'=>'13','level'=>'3','de'=>'Visuelle Kommunikation','en'=>'Visual communication','fr'=>'communication visuelle','it'=>'Comunicazione Visiva', 'shis'=>''),
		  '6095' => array('parent'=>'3119','order'=>'14','level'=>'3','de'=>'Produkt- und Industriedesign','en'=>'Industrial design','fr'=>'design industriel','it'=>'Disegno Industriale', 'shis'=>''),
		  '4832' => array('parent'=>'0','order'=>'15','level'=>'1','de'=>'Geisteswissenschaften','en'=>'Humanities','fr'=>'Lettres','it'=>'Lettere e Filosofia', 'shis'=>'1'),
		  '4761' => array('parent'=>'4832','order'=>'16','level'=>'2','de'=>'Philosophie','en'=>'Philosophy','fr'=>'Philosophie','it'=>'Filosofia', 'shis'=>'1300'),
		  '3867' => array('parent'=>'4832','order'=>'17','level'=>'2','de'=>'Theologie','en'=>'Theology','fr'=>'Théologie','it'=>'Teologia', 'shis'=>'1.1'),
		  '5633' => array('parent'=>'3867','order'=>'18','level'=>'3','de'=>'Protestantische Theologie','en'=>'Protestant theology','fr'=>'Théologie protestante','it'=>'Teologia Protestante', 'shis'=>'1205'),
		  '9787' => array('parent'=>'3867','order'=>'19','level'=>'3','de'=>'Römisch-katholische Theologie','en'=>'Roman catholic theology','fr'=>'Théologie catholique romaine','it'=>'Teologia Cattolica', 'shis'=>'1210'),
		  '6527' => array('parent'=>'3867','order'=>'20','level'=>'3','de'=>'Theologie fächerübergr./übrige','en'=>'General theology','fr'=>'Théologie interdisciplinaire','it'=>'Teologia interdisciplinare', 'shis'=>'1215/1201'),
		  '8796' => array('parent'=>'4832','order'=>'21','level'=>'2','de'=>'Geschichte','en'=>'History','fr'=>'Histoire','it'=>'Storia', 'shis'=>'1600'),
		  '7210' => array('parent'=>'4832','order'=>'22','level'=>'2','de'=>'Sprach- u. Literaturw. (SLW)','en'=>'Linguistics & Literature (LL)','fr'=>'Linguistique & littérature','it'=>'Linguistica e Letteratura', 'shis'=>'1.2'),
		  '7408' => array('parent'=>'7210','order'=>'23','level'=>'3','de'=>'Linguistik','en'=>'Linguistics','fr'=>'Linguistique','it'=>'Linguistica', 'shis'=>'1405'),
		  '4391' => array('parent'=>'7210','order'=>'24','level'=>'3','de'=>'Deutsche SLW','en'=>'German LL','fr'=>'Langue et littérature allemandes','it'=>'Lingua e letteratura tedesca', 'shis'=>'1410'),
		  '9472' => array('parent'=>'7210','order'=>'25','level'=>'3','de'=>'Französische SLW','en'=>'French LL','fr'=>'Langue et littérature françaises','it'=>'Lingua e letteratura francese', 'shis'=>'1415'),
		  '3468' => array('parent'=>'7210','order'=>'26','level'=>'3','de'=>'Italienische SLW','en'=>'Italian LL','fr'=>'Langue et littérature italiennes','it'=>'Lingua e letteratura italiana', 'shis'=>'1420'),
		  '5424' => array('parent'=>'7210','order'=>'27','level'=>'3','de'=>'Rätoromanische SLW','en'=>'Rhaeto-Romanic LL','fr'=>'Langue et littérature rhéto-romanches','it'=>'Lingua e letteratura retoromancia', 'shis'=>'1425'),
		  '9391' => array('parent'=>'7210','order'=>'28','level'=>'3','de'=>'Englische SLW','en'=>'English LL','fr'=>'Langue et littérature anglaises','it'=>'Lingua e letteratura inglese', 'shis'=>'1435'),
		  '6230' => array('parent'=>'7210','order'=>'29','level'=>'3','de'=>'Andere mod. Sprachen Europas','en'=>'Other modern European languages','fr'=>'autres langues modernes européennes','it'=>'Altre lingue moderne europee', 'shis'=>'1429/30/31/40/45'),
		  '9557' => array('parent'=>'7210','order'=>'30','level'=>'3','de'=>'Klass. Sprachen Europas','en'=>'Classical European languages','fr'=>'langues classiques européennes','it'=>'Lingue classiche europee', 'shis'=>'1449'),
		  '5676' => array('parent'=>'7210','order'=>'31','level'=>'3','de'=>'Andere nichteurop. Sprachen','en'=>'Other non-European languages','fr'=>'autes langues non-européennes','it'=>'Altre lingue non europee', 'shis'=>'1454/55/60/65'),
		  '7599' => array('parent'=>'7210','order'=>'32','level'=>'3','de'=>'Dolmetschen u. Uebersetzung','en'=>'Translation studies','fr'=>'Traduction & interprétation','it'=>'Traduzione e interpretariato', 'shis'=>'1470'),
		  '1438' => array('parent'=>'4832','order'=>'33','level'=>'2','de'=>'Archäologie','en'=>'Archeology','fr'=>'Archéologie','it'=>'Archeologia', 'shis'=>'1500'),
		  '8637' => array('parent'=>'0','order'=>'34','level'=>'1','de'=>'Sozialwissenschaften','en'=>'Social sciences','fr'=>'Sciences sociales','it'=>'Scienze sociali', 'shis'=>'1.4'),
		  '6005' => array('parent'=>'8637','order'=>'35','level'=>'2','de'=>'Psychologie ','en'=>'Psychology','fr'=>'Psychologie','it'=>'Psicologia', 'shis'=>'2000'),
		  '6525' => array('parent'=>'8637','order'=>'36','level'=>'2','de'=>'Soziologie ','en'=>'Sociology','fr'=>'Sociologie','it'=>'Sociologia', 'shis'=>'2200'),
		  '7288' => array('parent'=>'8637','order'=>'37','level'=>'2','de'=>'Sozialarbeit','en'=>'Social work','fr'=>'Travail social','it'=>'Lavoro sociale', 'shis'=>'2205'),
		  '1514' => array('parent'=>'8637','order'=>'38','level'=>'2','de'=>'Politikwissenschaft','en'=>'Political science','fr'=>'Sciences politiques','it'=>'Scienze Politiche', 'shis'=>'2300'),
		  '9619' => array('parent'=>'8637','order'=>'39','level'=>'2','de'=>'Kommunikations- u. Medienwissenschaften','en'=>'Communication and media studies','fr'=>'Médias & Communication','it'=>'Comunicazione e media', 'shis'=>'2400'),
		  '8367' => array('parent'=>'8637','order'=>'40','level'=>'2','de'=>'Ethnologie u. Volkskunde','en'=>'Ethnology','fr'=>'Ethnologie','it'=>'Etnologia', 'shis'=>'1990'),
		  '1774' => array('parent'=>'8637','order'=>'41','level'=>'2','de'=>'Frauen- / Geschlechterforschung','en'=>'Gender studies','fr'=>'Etudes genres','it'=>'Studi di genere', 'shis'=>'9001'),
		  '1861' => array('parent'=>'0','order'=>'42','level'=>'1','de'=>'Recht','en'=>'Law','fr'=>'Droit','it'=>'Diritto', 'shis'=>'3/2600'),
		  '4890' => array('parent'=>'1861','order'=>'43','level'=>'2','de'=>'Wirtschaftsrecht','en'=>'Business law','fr'=>'Droit économique','it'=>'Diritto economico', 'shis'=>''),
		  '6950' => array('parent'=>'0','order'=>'44','level'=>'1','de'=>'Wirtschaftswissenschaften','en'=>'Business','fr'=>'Sciences économiques','it'=>'Economia', 'shis'=>'2'),
		  '7290' => array('parent'=>'6950','order'=>'45','level'=>'2','de'=>'Volkswirtschaft','en'=>'Economics','fr'=>'Economie politique','it'=>'Economia politica', 'shis'=>'2505'),
		  '1676' => array('parent'=>'6950','order'=>'46','level'=>'2','de'=>'Betriebswirtschaft','en'=>'Business Administration','fr'=>'Economie d\'entreprise','it'=>'Economia aziendale', 'shis'=>'2520'),
		  '4949' => array('parent'=>'6950','order'=>'47','level'=>'2','de'=>'Wirtschaftsinformatik ','en'=>'Business Informatics','fr'=>'Informatique de gestion','it'=>'Informatica gestionale', 'shis'=>'2530'),
		  '2108' => array('parent'=>'6950','order'=>'48','level'=>'2','de'=>'Facility Management','en'=>'Facility Management','fr'=>'Facility Management','it'=>'Facility Management', 'shis'=>''),
		  '7641' => array('parent'=>'6950','order'=>'49','level'=>'2','de'=>'Hotellerie','en'=>'Hotel business','fr'=>'Hôtellerie','it'=>'Alberghiero', 'shis'=>''),
		  '6238' => array('parent'=>'6950','order'=>'50','level'=>'2','de'=>'Tourismus','en'=>'Tourism','fr'=>'Tourisme','it'=>'Turismo', 'shis'=>''),
		  '2990' => array('parent'=>'0','order'=>'51','level'=>'1','de'=>'Naturwissenschaften & Mathematik','en'=>'Natural sciences & Mathematics','fr'=>'Sciences naturelles & Mathématiques','it'=>'Scienze naturali & Matematica', 'shis'=>'4'),
		  '2158' => array('parent'=>'2990','order'=>'52','level'=>'2','de'=>'Mathematik','en'=>'Mathematics','fr'=>'Mathématiques','it'=>'Matematica', 'shis'=>'4200'),
		  '1266' => array('parent'=>'2990','order'=>'53','level'=>'2','de'=>'Informatik','en'=>'Computer science','fr'=>'Informatique','it'=>'Informatica', 'shis'=>'4300'),
		  '8990' => array('parent'=>'2990','order'=>'54','level'=>'2','de'=>'Astronomie','en'=>'Astronomy','fr'=>'Astronomie','it'=>'Astronomia', 'shis'=>'4400'),
		  '6986' => array('parent'=>'2990','order'=>'55','level'=>'2','de'=>'Physik','en'=>'Physics','fr'=>'Physique','it'=>'Fisica', 'shis'=>'4500'),
		  '6451' => array('parent'=>'2990','order'=>'56','level'=>'2','de'=>'Chemie','en'=>'Chemistry','fr'=>'Chimie','it'=>'Chimica', 'shis'=>'4600'),
		  '4195' => array('parent'=>'2990','order'=>'57','level'=>'2','de'=>'Biologie','en'=>'Biology','fr'=>'Biologie','it'=>'Biologia', 'shis'=>'4700'),
		  '7793' => array('parent'=>'4195','order'=>'58','level'=>'3','de'=>'Oekologie','en'=>'Ecology','fr'=>'Ecologie','it'=>'Ecologia', 'shis'=>'1000'),
		  '5255' => array('parent'=>'2990','order'=>'59','level'=>'2','de'=>'Erdwissenschaften','en'=>'Earth Sciences','fr'=>'Géologie','it'=>'Geologia', 'shis'=>'4800'),
		  '7950' => array('parent'=>'2990','order'=>'60','level'=>'2','de'=>'Geographie','en'=>'Geography','fr'=>'Géographie','it'=>'Geografia', 'shis'=>'4900'),
		  '9321' => array('parent'=>'0','order'=>'61','level'=>'1','de'=>'Technische & angewandte Wissenschaften','en'=>'Technology & Applied sciences','fr'=>'Technologie & Sciences appliquées','it'=>'Tecnologie & Scienze applicate', 'shis'=>'6'),
		  '7303' => array('parent'=>'9321','order'=>'62','level'=>'2','de'=>'Telekommunikation','en'=>'Telecommunication','fr'=>'Télecommunication','it'=>'Telecomunicazioni', 'shis'=>'7550'),
		  '5742' => array('parent'=>'9321','order'=>'63','level'=>'2','de'=>'Elektroingenieur','en'=>'Electrical Engineering','fr'=>'électrotechnique','it'=>'Elettrotecnica', 'shis'=>'7500'),
		  '8189' => array('parent'=>'9321','order'=>'64','level'=>'2','de'=>'Maschineningenieur','en'=>'Mechanical Engineering','fr'=>'mécanique technique','it'=>'Ingegneria Meccanica', 'shis'=>'7600'),
		  '8502' => array('parent'=>'8189','order'=>'65','level'=>'3','de'=>'Mikrotechnik ','en'=>'Microtechnology','fr'=>'Microtechnique','it'=>'Microtecnica', 'shis'=>'7450'),
		  '5324' => array('parent'=>'8189','order'=>'66','level'=>'3','de'=>'Automobiltechnik','en'=>'Automoive Engineering','fr'=>'Technique automobile','it'=>'Tecnica Automobilistica', 'shis'=>''),
		  '1566' => array('parent'=>'9321','order'=>'67','level'=>'2','de'=>'Materialwissenschaften ','en'=>'Material sciences','fr'=>'science des matériaux','it'=>'Scienze dei Materiali', 'shis'=>'7700'),
		  '7132' => array('parent'=>'9321','order'=>'68','level'=>'2','de'=>'Gebäudetechnik','en'=>'Building Engineering','fr'=>'Technique du bâtiment','it'=>'Tecnologia degli edifici & impiantistica', 'shis'=>''),
		  '2979' => array('parent'=>'9321','order'=>'69','level'=>'2','de'=>'Forstwirtschaft','en'=>'Forestry','fr'=>'Sylviculture','it'=>'Selvicoltutura', 'shis'=>'7905'),
		  '3624' => array('parent'=>'9321','order'=>'70','level'=>'2','de'=>'Agrarwirtschaft','en'=>'Agriculture','fr'=>'Agriculture & Agronomie','it'=>'Agricultura', 'shis'=>'7910'),
		  '1442' => array('parent'=>'3624','order'=>'71','level'=>'3','de'=>'Oenologie','en'=>'Enology','fr'=>'œnologie','it'=>'Enologia', 'shis'=>''),
		  '9768' => array('parent'=>'9321','order'=>'72','level'=>'2','de'=>'Lebensmitteltechnologie','en'=>'Food technology','fr'=>'Technologie alimentaire','it'=>'Tecnologia Alimentare', 'shis'=>'7915'),
		  '5727' => array('parent'=>'9321','order'=>'73','level'=>'2','de'=>'Chemieingenieurwesen ','en'=>'Chemical Engineering','fr'=>'Génie chimique','it'=>'Ingegneria Chimica', 'shis'=>'7400'),
		  '1892' => array('parent'=>'9321','order'=>'74','level'=>'2','de'=>'Biotechnologie','en'=>'Biotechnology','fr'=>'Biotechnologie','it'=>'Biotecnologia', 'shis'=>''),
		  '2850' => array('parent'=>'9321','order'=>'75','level'=>'2','de'=>'Umweltingenieurwesen','en'=>'Environmental Engineering','fr'=>'Génie de l\'environnement','it'=>'Ingegneria Civile', 'shis'=>''),
		  '9389' => array('parent'=>'9321','order'=>'76','level'=>'2','de'=>'Bauwesen','en'=>'Construction Science','fr'=>'Sciences de la construction','it'=>'Scienze dalla Costruzione', 'shis'=>'6.1'),
		  '2527' => array('parent'=>'9389','order'=>'77','level'=>'3','de'=>'Bauingenieurwesen','en'=>'Civil Engineering','fr'=>'Génie civil','it'=>'Genio Civile', 'shis'=>'7200'),
		  '9738' => array('parent'=>'9389','order'=>'78','level'=>'3','de'=>'Kulturtechnik und Vermessung','en'=>'Rural Engineering and Surveying','fr'=>'Génie rural et mensuration','it'=>'Ingegneria rurale & geomatica', 'shis'=>'7800'),
		  '4380' => array('parent'=>'9321','order'=>'79','level'=>'2','de'=>'Betriebs- und Produktionswissenschaften','en'=>'Production and Enterprise','fr'=>'Production et Entreprise','it'=>'Gestione della produzione e dei processi industriali', 'shis'=>'7650'),
		  '8220' => array('parent'=>'0','order'=>'80','level'=>'1','de'=>'Gesundheit & Medizin','en'=>'Health','fr'=>'Santé & Médecine','it'=>'Salute & Medicina', 'shis'=>'5'),
		  '5955' => array('parent'=>'8220','order'=>'81','level'=>'2','de'=>'Humanmedizin ','en'=>'Human medicine','fr'=>'Médecine humaine','it'=>'Medicina', 'shis'=>'5.1/6200'),
		  '2075' => array('parent'=>'8220','order'=>'82','level'=>'2','de'=>'Zahnmedizin ','en'=>'Dentistry','fr'=>'Odontologie','it'=>'Odontoiatria', 'shis'=>'5.2/6300'),
		  '3787' => array('parent'=>'8220','order'=>'83','level'=>'2','de'=>'Veterinärmedizin ','en'=>'Veterinary medicine','fr'=>'Médecine vétérinaire','it'=>'Veterinaria', 'shis'=>'5.3/6400'),
		  '3424' => array('parent'=>'8220','order'=>'84','level'=>'2','de'=>'Pharmazie ','en'=>'Pharmacy','fr'=>'Pharmacie','it'=>'Farmacia', 'shis'=>'5.4/6500'),
		  '5516' => array('parent'=>'8220','order'=>'85','level'=>'2','de'=>'Pflege','en'=>'Nursing','fr'=>'Sciences infirmières','it'=>'Cure infermieristiche ', 'shis'=>'6150'),
		  '4864' => array('parent'=>'8220','order'=>'86','level'=>'2','de'=>'Therapie','en'=>'Therapy','fr'=>'Thérapie','it'=>'Terapia', 'shis'=>''),
		  '7072' => array('parent'=>'4864','order'=>'87','level'=>'3','de'=>'Physiotherapie','en'=>'Physiotherapy','fr'=>'Physiothérapie','it'=>'Fisioterapia', 'shis'=>''),
		  '6688' => array('parent'=>'4864','order'=>'88','level'=>'3','de'=>'Ergotherapie','en'=>'Occupational therapy','fr'=>'Ergothérapie','it'=>'Ergoterapia', 'shis'=>''),
		  '5214' => array('parent'=>'0','order'=>'89','level'=>'1','de'=>'Erziehungswissenschaften','en'=>'Education','fr'=>'Éducation','it'=>'Educazione', 'shis'=>'2100'),
		  '9955' => array('parent'=>'5214','order'=>'90','level'=>'2','de'=>'Lehrkräfteausbildung','en'=>'Teacher education','fr'=>'Formation des enseignants','it'=>'Formazione degli insegnanti', 'shis'=>''),
		  '6409' => array('parent'=>'9955','order'=>'91','level'=>'3','de'=>'Vorschul- und Primarstufe allgemein','en'=>'Primary school','fr'=>'École primaire','it'=>'Scuola elementare', 'shis'=>''),
		  '7008' => array('parent'=>'9955','order'=>'92','level'=>'3','de'=>'Sekundarstufe I allgemein','en'=>'Secondary school I','fr'=>'École secondaire I','it'=>'Scuola media', 'shis'=>'1190/4103'),
		  '4233' => array('parent'=>'9955','order'=>'93','level'=>'3','de'=>'Sekundarstufe II allgemein (Maturitätsschulen)','en'=>'Secondary school II','fr'=>'École secondaire II (lycée)','it'=>'Scuola superiore', 'shis'=>''),
		  '1672' => array('parent'=>'5214','order'=>'94','level'=>'2','de'=>'Logopädie','en'=>'Logopedics','fr'=>'Logopédie','it'=>'Logopedia', 'shis'=>''),
		  '1406' => array('parent'=>'5214','order'=>'95','level'=>'2','de'=>'Pädagogik','en'=>'Pedagogy','fr'=>'Pédagogie','it'=>'Pedagogia', 'shis'=>''),
		  '2150' => array('parent'=>'1406','order'=>'96','level'=>'3','de'=>'Sonderpädagogik ','en'=>'Special education','fr'=>'Pédagogie spécialisée','it'=>'Pedagogia speciale', 'shis'=>'2120'),
		  '3822' => array('parent'=>'1406','order'=>'97','level'=>'3','de'=>'Heilpädagogik','en'=>'Orthopedagogy','fr'=>'Pédagogie curative','it'=>'Pedagogia curativa', 'shis'=>''),
		  '5889' => array('parent'=>'0','order'=>'98','level'=>'1','de'=>'Interdisziplinäre & Andere','en'=>'Interdisciplinary & Other','fr'=>'Interdisciplinaire & Autre','it'=>'Interdisciplinare & Altre', 'shis'=>''),
		  '6059' => array('parent'=>'5889','order'=>'99','level'=>'2','de'=>'Information & Dokumentation','en'=>'Information & documentation','fr'=>'Information & documentation','it'=>'Archivistica & biblioteconomia & documentazione', 'shis'=>''),
		  '8683' => array('parent'=>'5889','order'=>'100','level'=>'2','de'=>'Sport','en'=>'Sport','fr'=>'Sport','it'=>'Sport', 'shis'=>'2130'),
		  '5561' => array('parent'=>'5889','order'=>'101','level'=>'2','de'=>'Militärwissenschaften','en'=>'Military sciences','fr'=>'Sciences militaires','it'=>'Scienze Militari', 'shis'=>'8000')
		);
		global $USER;
		foreach($disciplines as $key => &$discipline){
			$discipline['pid'] = $key;
			$discipline['title'] = $discipline[$USER->lang];
		}

		foreach($disciplines as $discipline){
			$parent = $discipline['parent'];
			$pid = $discipline['pid'];
			$title = $discipline['title'];
			$disciplines[$parent]['children'][$pid] = $title;
		}

		foreach($disciplines as &$discipline){
			if(isset($discipline['children'])){
				$children = $discipline['children'];
				asort($children);
				$discipline['children'] = $children;
			}else{
				$discipline['children'] = array();
			}
		}
		return $disciplines;

	}

	protected function get_object_by_label($label, $owner, $collection=''){
		$label = preg_quote($label);
		$label = str_replace('\\', '\\\\', $label);
		$query = 'select ?pid ?label ?lastModifiedDate ?ownerId from <#ri> where{'; '';
		$query .= '?pid <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0> . ';
		$query .= '?pid <fedora-view:lastModifiedDate> ?lastModifiedDate . ';
		$query .= '?pid <fedora-model:label> ?label FILTER regex(?label , "^'.$label.'$", "i") . ';
		$query .= '?pid <fedora-model:ownerId> ?ownerId  . ';
		$query .= "?pid <fedora-model:ownerId> '$owner' . " ;
		$query .= 'OPTIONAL {?pid <fedora-rels-ext:isCollection> ?col} FILTER( !BOUND(?col) || !?col) ';
		$query .= '} ORDER BY DESC(?lastModifiedDate) LIMIT 50 ';

		$fedora = $this->get_fedora();
		$items = $fedora->ri_search($query, '', 'tuples', 'Sparql', 'Sparql');

		$result = false;
		foreach($items as &$item){
			$pid = str_replace('info:fedora/', '', $item['pid']['@uri']);
			$item['pid'] = $pid;
			if($result == false){
				$result = $item;
			}else{
				if($result['lastmodifieddate']<$item['lastmodifieddate']){
					$result = $item;
				}
			}
		}
		$result = $result ? $result['pid'] : false;
		return $result;
	}

	protected function update_repository_object($content, fedora_object_meta $meta, SWITCH_object_meta $switch){
		$pid = $meta->pid;
		$name = $label = $meta->label;
		$mime_type = $meta->mime;
		$this->update_data($pid, $name, $content, $mime_type);
		$this->update_label($pid, $label);
		$this->update_metadata($pid, $meta, $switch);
	}

	protected function update_label($pid, $label){
		$fedora = $this->get_fedora();
		$fedora->modify_object($pid, $label);
		$fedora->modify_datastream($pid, 'DS1', $label);
	}

	protected function update_metadata($pid, fedora_object_meta $data, SWITCH_object_meta $switch){
		$meta = new fedora_object_meta();
		$meta->pid = $pid;

		$fedora = $this->get_fedora();
		$content = SWITCH_get_rels_ext($meta, $switch);
		$fedora->modify_datastream($pid, 'RELS-EXT', 'Relationships to other objects', $content, 'application/rdf+xml');
		$content = SWITCH_get_chor_dc($meta, $switch);
		$fedora->update_datastream($pid, 'CHOR_DC', 'SWITCH CHOR_DC record for this object', $content, 'text/xml');
	}

	protected function update_data($pid, $name, $content, $mime_type){
		$fedora = $this->get_fedora();
		$fedora->update_datastream($pid, 'DS1', $name, $content, $mime_type, false);
	}
}