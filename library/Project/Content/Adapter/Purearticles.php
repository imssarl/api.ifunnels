<?php
 
/**
 * Purearticles контент функционал
 */

class Project_Content_Adapter_Purearticles implements Project_Content_Interface {

	private $_tags=array('body'=>'{body}');
	protected $_limit=20; // лимит вывода English статей
	protected $_counter=0;
	private $_paging=array();
	private $_post=array();
	private $_res=array();
	protected $_withKeywords=false; // поиск по тегам (keywords)
	protected $_withCategory=array(); // c данными категориями category_id
	protected $_withTags=false; // поиск по тегам
	protected $_isNotEmpty=false; // для проверки результата выборки (по умолчанию выборка пуста) отражает результаты последнего getList
	protected $result; //результат curl
	private $_withJson=false;
	private $_withRewrite=false;

	public static $flags=array(
		1=>array( 'title'=>'English' ), //5 => array('url' => 'http://www.articlesbase.com', 'tail' => 'en') // english
		2=>array( 'url' => 'http://www.articlonet.fr', 'tail' => 'fr', 'title'=>'French' ),// french
		3=>array( 'url' => 'http://www.artigonal.com', 'tail' => 'pr', 'title'=>'Portuguese' ),// portugese
		4=>array( 'url' => 'http://www.articuloz.com', 'tail' => 'sp', 'title'=>'Spanish' ),// spanish
	);

	protected $numofcontent=array(
		0 => 15,
		1 => 10
	);

	protected $_arrCategoriesUrl = array ( 2 => array ( 21 => '/arobic-articles/', 22 => '/basketball-articles/', 23 => '/fitness-articles/', 24 => '/football-articles/', 25 => '/musculation-articles/', 26 => '/sports-de-glisse-articles/', 27 => '/sports-extrmes-articles/', 28 => '/sports-aquatiques-articles/', 29 => '/tennis-articles/', 30 => '/yoga-articles/', 31 => '/astrologie-articles/', 32 => '/culture-articles/', 33 => '/economie-articles/', 34 => '/environnement-articles/', 35 => '/journalisme-articles/', 36 => '/ong-et-associations-articles/', 37 => '/politique-articles/', 38 => '/religion-articles/', 39 => '/carrieres-articles/', 40 => '/entrainement-aux-affaires-articles/', 41 => '/emploi-articles/', 42 => '/entreprise-a-domicile-articles/', 43 => '/equipements-articles/', 44 => '/gestion-articles/', 45 => '/petite-entreprise-articles/', 46 => '/publicite-articles/', 47 => '/ventes-articles/', 48 => '/alimentation-bio-articles/', 49 => '/boissons-articles/', 50 => '/cafe-articles/', 51 => '/chocolat-articles/', 52 => '/conseil-de-cuisine-articles/', 53 => '/recettes-articles/', 54 => '/vin-articles/', 55 => '/art-articles/', 56 => '/design-numerique-articles/', 57 => '/films-articles/', 58 => '/humour-articles/', 59 => '/jeux-vidos-articles/', 60 => '/littrature-articles/', 61 => '/musique-articles/', 62 => '/les-jeux-de-hasard-articles/', 63 => '/photographie-articles/', 64 => '/poesie-articles/', 65 => '/sorties-articles/', 66 => '/television-articles/', 67 => '/theatre-articles/', 68 => '/cheveux-articles/', 69 => '/cosmetique-articles/', 70 => '/ongles-articles/', 71 => '/soins-de-la-peau-articles/', 72 => '/gps-articles/', 73 => '/telephones-mobiles-articles/', 74 => '/tv-par-satellite-articles/', 75 => '/videoconference-articles/', 76 => '/voip-articles/', 77 => '/brevets-articles/', 78 => '/blessures-personnelles-articles/', 79 => '/droit-dauteur-articles/', 80 => '/mauvaises-pratiques-medic-articles/', 81 => '/legalite-sur-internet-articles/', 82 => '/propriete-intellectuelle-articles/', 83 => '/education-a-domicile-articles/', 84 => '/education-des-enfants-articles/', 85 => '/education-a-distance-articles/', 86 => '/faculte-articles/', 87 => '/langues-articles/', 88 => '/science-articles/', 89 => '/coaching-articles/', 90 => '/conseils-articles/', 91 => '/dpendances-articles/', 92 => '/fixer-un-objectif-articles/', 93 => '/gestion-du-temps-articles/', 94 => '/hypnose-articles/', 95 => '/entrainement-articles/', 96 => '/assurance-articles/', 97 => '/banqueroute-articles/', 98 => '/immobilier-articles/', 99 => '/investissement-articles/', 100 => '/credit-articles/', 101 => '/taxes-articles/', 102 => '/blogs-et-forums-articles/', 103 => '/creation-de-programmes-pu-articles/', 104 => '/e-commerce-articles/', 105 => '/hebergement-articles/', 106 => '/marketing-sur-internet-articles/', 107 => '/publicite-en-ligne-articles/', 108 => '/optimisation-de-moteur-de-articles/', 109 => '/web-design-articles/', 110 => '/amenagement-de-la-maison-articles/', 111 => '/animaux-domestiques-articles/', 112 => '/nourrissons-articles/', 113 => '/grossesse-articles/', 114 => '/outils-et-equipements-articles/', 115 => '/parents-articles/', 116 => '/reparations-a-la-maison-articles/', 117 => '/securite-a-la-maison-articles/', 118 => '/conseils-en-marketing-articles/', 119 => '/dossiers-de-presse-articles/', 120 => '/marketing-mlm-articles/', 121 => '/marketing-par-affiliation-articles/', 122 => '/informatique-mobile-articles/', 123 => '/jeux-articles/', 124 => '/logiciel-articles/', 125 => '/materiel-informatique-articles/', 126 => '/recuperation-de-donnees-articles/', 127 => '/reseau-articles/', 128 => '/securite-articles/', 129 => '/amitis-articles/', 130 => '/divorces-articles/', 131 => '/mariages-articles/', 132 => '/rencontres-articles/', 133 => '/sparations-articles/', 134 => '/sexualit-articles/', 135 => '/allergies-articles/', 136 => '/bien-tre-articles/', 137 => '/handicaps-articles/', 138 => '/maladies-articles/', 139 => '/mdecines-alternatives-articles/', 140 => '/meditation-articles/', 141 => '/manger-articles/', 142 => '/regimes-articles/', 143 => '/sant-fminine-articles/', 144 => '/sant-masculine-articles/', 145 => '/sant-mentale-articles/', 146 => '/soins-dentaires-articles/', 147 => '/sommeil-articles/', 148 => '/accessoires-articles/', 149 => '/bijouterie-articles/', 150 => '/cadeaux-articles/', 151 => '/habillement-articles/', 152 => '/camping-articles/', 153 => '/conseils-de-voyage-articles/', 154 => '/croisiere-articles/', 155 => '/hotels-articles/', 156 => '/locations-de-vacances-articles/', 157 => '/sites-exotiques-articles/', 158 => '/transports-articles/', ), 3 => array ( 28 => '/arte-artigos/', 29 => '/literatura1-artigos/', 30 => '/musica-artigos/', 31 => '/automobilismo-artigos/', 32 => '/carros-artigos/', 33 => '/industria-automotiva-artigos/', 34 => '/motocicletas-artigos/', 35 => '/cirurgia-plastica-artigos/', 36 => '/cosmeticos-artigos/', 37 => '/joias-bijuterias-artigos/', 38 => '/moda-artigos/', 39 => '/carreira-artigos/', 40 => '/entrevistas-artigos/', 41 => '/recursos-humanos-artigos/', 42 => '/adolescentes-artigos/', 43 => '/animais-de-estimacao-artigos/', 44 => '/bebes-artigos/', 45 => '/casamento-artigos/', 46 => '/divorcio-artigos/', 47 => '/gravidez-artigos/', 48 => '/mulher-artigos/', 49 => '/cozinhas-artigos/', 50 => '/decoracao-artigos/', 51 => '/ferramentas-artigos/', 52 => '/jardinagem-artigos/', 53 => '/moveis-artigos/', 54 => '/seguranca-da-casa-artigos/', 55 => '/biologia-artigos/', 56 => '/quimica-artigos/', 57 => '/cronicas-artigos/', 58 => '/receitas-artigos/', 59 => '/direito-tributario-artigos/', 60 => '/doutrina-artigos/', 61 => '/jurisprudencia-artigos/', 62 => '/legislacao-artigos/', 63 => '/ciencia-artigos/', 64 => '/educacao-infantil-artigos/', 65 => '/educacao-online-artigos/', 66 => '/ensino-superior-artigos/', 67 => '/linguas-artigos/', 68 => '/esportes-radicais-artigos/', 69 => '/lutas-artigos/', 70 => '/yoga-artigos/', 71 => '/credito-artigos/', 72 => '/financas-pessoais-artigos/', 73 => '/investimentos-artigos/', 74 => '/seguro-artigos/', 75 => '/futebol-artigos/', 76 => '/blogs-artigos/', 77 => '/comercio-eletronico-artigos/', 78 => '/hospedagem-artigos/', 79 => '/marketing-na-internet-artigos/', 80 => '/seo-e-sem-artigos/', 81 => '/web-design-artigos/', 82 => '/ficcao-artigos/', 83 => '/poesia-artigos/', 84 => '/design-grafico-artigos/', 85 => '/marketing-internacional-artigos/', 86 => '/marketing-pessoal-artigos/', 87 => '/multimidia-artigos/', 88 => '/administracao-artigos/', 89 => '/atendimento-ao-cliente-artigos/', 90 => '/ger-de-projetos-artigos/', 91 => '/gerencia-artigos/', 92 => '/gestao-artigos/', 93 => '/negocio-de-casa-artigos/', 94 => '/negocios-online-artigos/', 95 => '/pequenas-empresas-artigos/', 96 => '/vendas-artigos/', 97 => '/cotidiano-artigos/', 98 => '/desigualdades-sociais-artigos/', 99 => '/meio-ambiente-artigos/', 100 => '/politica-artigos/', 101 => '/press-release-artigos/', 102 => '/auto-ajuda-artigos/', 103 => '/gerencia-de-tempo-artigos/', 104 => '/psicoterapia-artigos/', 105 => '/reducao-de-stress-artigos/', 106 => '/amizade-artigos/', 107 => '/relacoes-amorosas-artigos/', 108 => '/sexualidade-artigos/', 109 => '/astrologia-artigos/', 110 => '/evangelho-artigos/', 111 => '/meditacao-artigos/', 112 => '/religiao-artigos/', 113 => '/medicina-artigos/', 114 => '/medicina-alternativa-artigos/', 115 => '/nutricao-artigos/', 116 => '/odontologia-artigos/', 117 => '/hardware-artigos/', 118 => '/jogos-artigos/', 119 => '/laptops-artigos/', 120 => '/programacao-artigos/', 121 => '/seguranca-artigos/', 122 => '/software-artigos/', 123 => '/tecnologias-artigos/', 124 => '/telefonia-e-celular-artigos/', 125 => '/ti-artigos/', 126 => '/dicas-de-viagem-artigos/', 127 => '/hoteis-e-resorts-artigos/', ), 4 => array ( 30 => '/alimentos-organicos-articulos/', 31 => '/bajas-calorias-articulos/', 32 => '/cafe-articulos/', 33 => '/chocolate-articulos/', 34 => '/ensaladas-articulos/', 35 => '/pastas-articulos/', 36 => '/postres-articulos/', 37 => '/primer-plato-articulos/', 38 => '/recetas-articulos/', 39 => '/sopas-articulos/', 40 => '/sugerencias-de-cocina-articulos/', 41 => '/te-articulos/', 42 => '/tragos-articulos/', 43 => '/vino-articulos/', 44 => '/arte-articulos/', 45 => '/cine-articulos/', 46 => '/diseno-digital-articulos/', 47 => '/fotografia-articulos/', 48 => '/guion-articulos/', 49 => '/humor-articulos/', 50 => '/juegos-de-azar-articulos/', 51 => '/musica-articulos/', 52 => '/peliculas-articulos/', 53 => '/teatro-articulos/', 54 => '/television-articulos/', 55 => '/coaching-articulos/', 56 => '/consejos-articulos/', 57 => '/fijacion-de-objetivos-articulos/', 58 => '/gestion-del-tiempo-articulos/', 59 => '/alquiler-articulos/', 60 => '/berlina-o-sedan-articulos/', 61 => '/deportivos-articulos/', 62 => '/familiar-articulos/', 63 => '/motocicletas-articulos/', 64 => '/seguros-de-automovil-articulos/', 65 => '/todoterreno-articulos/', 66 => '/cirugias-plasticas-articulos/', 67 => '/consejos_belleza-articulos/', 68 => '/maquillaje-articulos/', 69 => '/productos-articulos/', 70 => '/tratamientos-articulos/', 71 => '/internet-de-banda-ancha-articulos/', 72 => '/gps-articulos/', 73 => '/telefonos-moviles-articulos/', 74 => '/tv-por-satelite-articulos/', 75 => '/videoconferencias-articulos/', 76 => '/voip-articulos/', 77 => '/artes-marciales-articulos/', 78 => '/atletismo-articulos/', 79 => '/automovilismo-articulos/', 80 => '/basket-articulos/', 81 => '/ciclismo-articulos/', 82 => '/deportes-de-aventura-articulos/', 83 => '/equitacion-articulos/', 84 => '/esqui-articulos/', 85 => '/fitness-articulos/', 86 => '/futbol-articulos/', 87 => '/gimnasia-articulos/', 88 => '/motociclismo-articulos/', 89 => '/natacion-articulos/', 90 => '/pesca-articulos/', 91 => '/tenis-articulos/', 92 => '/ciencia-articulos/', 93 => '/e-learning-articulos/', 94 => '/escuela-en-casa-articulos/', 95 => '/escuelas-articulos/', 96 => '/historia-articulos/', 97 => '/idiomas-articulos/', 98 => '/universidadesacademias-articulos/', 99 => '/curriculums-articulos/', 100 => '/sugerencias-articulos/', 101 => '/astrologia-articulos/', 102 => '/cabala-articulos/', 103 => '/cristianismo-articulos/', 104 => '/feng-shui-articulos/', 105 => '/grafologia-articulos/', 106 => '/islam-articulos/', 107 => '/judaismo-articulos/', 108 => '/meditacion-articulos/', 109 => '/metafisica-articulos/', 110 => '/misticismo-articulos/', 111 => '/new-age-articulos/', 112 => '/numerologia-articulos/', 113 => '/reiki-articulos/', 114 => '/religion-articulos/', 115 => '/bancarrota-articulos/', 116 => '/creditos-articulos/', 117 => '/forex-articulos/', 118 => '/impuestos-articulos/', 119 => '/inversiones-articulos/', 120 => '/jubilacion-articulos/', 121 => '/prestamos-articulos/', 122 => '/propiedad-inmobiliaria-articulos/', 123 => '/seguros-articulos/', 124 => '/animales-domesticos-articulos/', 125 => '/compras-articulos/', 126 => '/embarazo-articulos/', 127 => '/jardineria-articulos/', 128 => '/mejoras-del-hogar-articulos/', 129 => '/ninos-articulos/', 130 => '/reparaciones-domesticas-articulos/', 131 => '/seguridad-domestica-articulos/', 132 => '/ser-padres-articulos/', 133 => '/alojamiento-web-articulos/', 134 => '/blogs-articulos/', 135 => '/comercio-electronico-articulos/', 136 => '/copywriting-articulos/', 137 => '/correo-electronico-articulos/', 138 => '/diseno-web-articulos/', 139 => '/dominios-articulos/', 140 => '/link-popularity-articulos/', 141 => '/marketing-en-internet-articulos/', 142 => '/publicidad-articulos/', 143 => '/seo-articulos/', 144 => '/video-articulos/', 145 => '/copyright-articulos/', 146 => '/la-ley-en-internet-articulos/', 147 => '/lesiones-personales-articulos/', 148 => '/ley-cibernetica-articulos/', 149 => '/marcas-comerciales-articulos/', 150 => '/negligencias-medicas-articulos/', 151 => '/patentes-articulos/', 152 => '/propiedad-intelectual-articulos/', 153 => '/biografias-articulos/', 154 => '/drama-articulos/', 155 => '/ensayos-articulos/', 156 => '/ficcion-articulos/', 157 => '/infantil-articulos/', 158 => '/juvenil-articulos/', 159 => '/leyendas-articulos/', 160 => '/misterio-articulos/', 161 => '/no-ficcion-articulos/', 162 => '/poesia-articulos/', 163 => '/marketing-de-afiliacion-articulos/', 164 => '/marketing-mlm-articulos/', 165 => '/marketing-viral-articulos/', 166 => '/notas-de-prensa-articulos/', 167 => '/acuacultura-articulos/', 168 => '/gatos-articulos/', 169 => '/perros-articulos/', 170 => '/cardiologia-articulos/', 171 => '/dermatologia-articulos/', 172 => '/fonoaudiologia-articulos/', 173 => '/inmunologia-articulos/', 174 => '/odontologia-articulos/', 175 => '/oftalmologia-articulos/', 176 => '/ortopedia-articulos/', 177 => '/pediatria-articulos/', 178 => '/bisuteria-articulos/', 179 => '/calzado-articulos/', 180 => '/cosmetica-articulos/', 181 => '/joyeria-articulos/', 182 => '/ropa-articulos/', 183 => '/monografias-articulos/', 184 => '/instrumentos-articulos/', 185 => '/jazz-y-blues-articulos/', 186 => '/musica-clasica-articulos/', 187 => '/rock-articulos/', 188 => '/administracion-articulos/', 189 => '/coaching-empresarial-articulos/', 190 => '/companias-publicas-articulos/', 191 => '/direccion-de-proyectos-articulos/', 192 => '/empleo-articulos/', 193 => '/emprendedores-articulos/', 194 => '/empresas-articulos/', 195 => '/etica-articulos/', 196 => '/financiamiento-articulos/', 197 => '/franquicias-articulos/', 198 => '/ideas-de-negocios-articulos/', 199 => '/liderazgo-articulos/', 200 => '/management-articulos/', 201 => '/marcas-articulos/', 202 => '/negocios-desde-el-hogar-articulos/', 203 => '/negocios-en-linea-articulos/', 204 => '/negocios-internacionales-articulos/', 205 => '/oportunidad-de-negocios-articulos/', 206 => '/orgsin-fines-de-lucro-articulos/', 207 => '/outsourcing-articulos/', 208 => '/pequenos-negocios-articulos/', 209 => '/planificacion-estrategica-articulos/', 210 => '/presentaciones-articulos/', 211 => '/profesiones-articulos/', 212 => '/publicidad-articulos/', 213 => '/recursos-humanos-articulos/', 214 => '/redes-articulos/', 215 => '/relaciones-publicas-articulos/', 216 => '/servicio-al-cliente-articulos/', 217 => '/ventas-articulos/', 218 => '/cultura-articulos/', 219 => '/deportes-articulos/', 220 => '/economia-articulos/', 221 => '/educacion-articulos/', 222 => '/medio-ambiente-articulos/', 223 => '/politica-articulos/', 224 => '/religion-articulos/', 225 => '/salud-articulos/', 226 => '/sociedad-articulos/', 227 => '/turismo-articulos/', 228 => '/general-articulos/', 229 => '/parapsicologia-articulos/', 230 => '/psicoanalisis-articulos/', 231 => '/psicologia-evolutiva-articulos/', 232 => '/psicologia-laboral-articulos/', 233 => '/psicologia-paranormal-articulos/', 234 => '/psicopedagogia-articulos/', 235 => '/psicoterapia-articulos/', 236 => '/amistad-articulos/', 237 => '/bodas-articulos/', 238 => '/citas-seduccion-articulos/', 239 => '/divorcio-articulos/', 240 => '/infidelidad-articulos/', 241 => '/matriomonio-articulos/', 242 => '/separacion-articulos/', 243 => '/sexualidad-articulos/', 244 => '/adicciones-articulos/', 245 => '/aerobic-articulos/', 246 => '/antienvejecimiento-articulos/', 247 => '/cancer-articulos/', 248 => '/comer-articulos/', 249 => '/deporte-articulos/', 250 => '/dietas-articulos/', 251 => '/dormir-articulos/', 252 => '/enfermedades-articulos/', 253 => '/entrenamiento-muscular-articulos/', 254 => '/gimnasia-terapeutica-articulos/', 255 => '/medicina-alternativa-articulos/', 256 => '/meditacion-articulos/', 257 => '/nuevos-descubrimientos-articulos/', 258 => '/ac-articulos/', 259 => '/celulares-articulos/', 260 => '/hardware-articulos/', 261 => '/informatica-movil-articulos/', 262 => '/juegos-articulos/', 263 => '/programacion-articulos/', 264 => '/recuperacion-de-datos-articulos/', 265 => '/redes-articulos/', 266 => '/seguridad-articulos/', 267 => '/software-articulos/', 268 => '/tecnologia-articulos/', 269 => '/camping-articulos/', 270 => '/cruceros-articulos/', 271 => '/hoteles-articulos/', 272 => '/lugares-exoticos-articulos/', 273 => '/sugerencias-sobre-viajes-articulos/', 274 => '/turismo-salud-articulos/', )); 

	private $itemsKey = 0; // индекс элемента для добавления в проект
	private $_pagedData = false;
	private $_pageSettings = '';

	public function __construct() {
		$itemsKey = 0;
		if (empty($_SESSION['pagedData'])) {
			$_SESSION['pagedData'] = array();
		}
		$this->_pagedData =&$_SESSION['pagedData'];
		$this->_pageSettings =&$_SESSION['pageSettings'];
	}

	private function getByIds( &$arrRes ) {
		if ( $this->_filter['flg_language']==1 ) {
			try{
				Core_Sql::setConnectToServer( 'articles.db' );
				$arrRes=Core_Sql::getAssoc( 'SELECT title, body, summary, resource  FROM articles WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
				foreach( $arrRes as &$_item){
					$_item['body']=$_item['body'].'<br/>'.$_item['resource'];
				}
				Core_Sql::renewalConnectFromCashe();
			} catch(Exception $e) {
				return false;
			}
		} else {
			$dataWithBody = array();
			set_time_limit(0);
			ignore_user_abort(true);	//это для загрузки body выбранного контента.
			foreach ( $this->_withIds as $content ) {
				$curlData=Core_Curl::getInstance();
				if ( !$curlData->getContent( $this->_pagedData[$content]['url'] ) ) {
					break;
				}
				$result2=$curlData->getResponce();
				if ($result2 === false) {
					$body[1] = 'no data';
				} else {
					preg_match( '/\<!--INFOLINKS_ON--\>(.*)\<!--INFOLINKS_OFF--\>/isU', $result2, $body);
				}
				$this->_pagedData[$content]['body'] = $body[1];
				$dataWithBody[] = $this->_pagedData[$content];
			}
			$arrRes = $dataWithBody;
		}
		$this->_pagedData = false; 
		$this->_pageSettings = array();
		return !empty( $arrRes );
	}

	private function getByFilter( &$arrRes ) {
		$this->_paging['curpage']=(!empty($this->_paging['curpage']))?$this->_paging['curpage']:1;
		if ( $this->_filter['flg_language']==1 ) {
			$limitstart = ($this->_paging['curpage']-1)*$this->_limit;
			$limitend = $this->_paging['curpage']*$this->_limit;
			Core_Sql::setConnectToServer( 'sphinx.search' );
			$arrRes=Core_Sql::getAssoc("
				SELECT *, WEIGHT() myweight 
				FROM purearticles_english ".
				((!empty( $this->_filter['keywords'] )||!empty( $this->_filter['category_id'] ) )? " WHERE ":'').
				( ( !empty( $this->_filter['keywords'] ) )?
				"MATCH(".Core_Sql::fixInjection( $this->_filter['keywords'] ).") ":
				"")
				.((!empty($this->_filter['category_id']))?" ".((!empty($this->_filter['keywords']))?"AND":'')." category_id=".$this->_filter['category_id']:'')."
				ORDER BY myweight DESC 
				LIMIT ".$limitstart.", ".$limitend);
			$this->_pageSettings['recall']=Core_Sql::getKeyVal( 'SHOW META' ); // общее колличество найденых
			Core_Sql::renewalConnectFromCashe();
			$this->_pagedData = array_merge( $this->_pagedData, $arrRes );
		}else{
			$_page=$this->_paging['curpage'];
			do{
				$_arrRes=array();
				if ( !$this->curlContent( $_arrRes ) ){
					return $this;
				}
				$this->_pagedData = array_merge( $this->_pagedData, $_arrRes );
				$this->_paging['curpage']++;
			}while( !empty( $_arrRes ) && count( $this->_pagedData )<$this->_limit+$this->_counter );
			$this->_paging['curpage']=$_page;
		}
		$this->_pageSettings['page'] = (int)$this->_paging['curpage'];
		if ( $this->_pageSettings['recall']['total_found'] >= ($this->_pageSettings['page']+1)*$this->_limit ) {
			$this->_pageSettings['nextpage'] = $this->_paging['curpage']+1;
		} else {
			$this->_pageSettings['nextpage'] = 0;
		}
		$arrForPage = array_chunk( $this->_pagedData, $this->_limit , TRUE );
		$arrRes = $arrForPage[$this->_paging['curpage']-1];
		return !empty( $arrRes );
	}
	
	private function curlContent ( &$arrRes ){
		$curlContent=Core_Curl::getInstance();
		if ( !$curlContent->withCookie( self::$flags[$this->_filter['flg_language']]['url'] )->getContent( $this->getUrl() ) ) {
			return false;
		}
		$this->result=$curlContent->getResponce();
		if ($this->result === false) {
			$arrRes = array();
		}else{
			$this->parseResult( $arrRes );
		}
		return true;
	}

	private function parseResult( &$mixRes) {
		preg_match ( '/\<a href\="(.*)" class\="next" name\="paging_link"\>(.*)\<\/a\>/iU' , $this->result, $haveNext );
		if ( ! empty( $haveNext[2] ) ) {
			$this->_pageSettings['nextpage'] = $this->_paging['curpage']+1;
			$this->_pageSettings['page'] = $this->_paging['curpage']+0;
		} else {
			$this->_pageSettings['nextpage'] = 0;
			$this->_pageSettings['page'] = $this->_paging['curpage']+0;
		}
		preg_match( '/\<div id\="tab1"\>(.*)\<div id\="tab2"\>/isU', $this->result, $resultDiv);
		if ( empty ($resultDiv) ) {
			preg_match( '/\<div class\="search_pg_col_left"\>(.*)\<div class\="searches_related"\>/isU', $this->result, $resultDiv);
		}
		preg_match_all( '/\<div class\="article_row"\>\\n{1,} {1,}\<div class\="title"\>\\n {1,}\<h3\>\<a title\="(.*)" href\="(.*)"(.*)\>(.*)\<\/a\>\<\/h3\>(.*)\<span\>(\d{2})\/(\d{2})\/(\d{4})\<\/span\>/isU', $resultDiv[1], $arrData);
		unset($arrData[0],$arrData[1],$arrData[3],$arrData[6],$resultDiv);
		$this->itemsKey = count($this->_pagedData);
		foreach ( $arrData[4] as $key => $v ) {
			preg_match( '/name"\>\<a href\="(.*)" title\="(.*)"\>(.*)\<\/a\>\<\/span\>\<span class\="separator"\>(.*)\<a href\="(.*)" title\="(.*)"\>(.*)\<\/a\>\<span class\="separator"\>\>\<\/span\>(.*)\<a href\="(.*)" title\="(.*)"\>(.*)\</isU', $arrData[5][$key], $author);
			if ( empty($author) ) {
				preg_match( '/name"\>\<a href\="(.*)" title\="(.*)"\>(.*)\<\/a\>\<\/span\>\<span class\="separator"\>(.*)\<a href\="(.*)" title\="(.*)"\>(.*)\<\/a\>(.*)\</isU', $arrData[5][$key], $author);
				$author[11] = $author[7];
			}
			$mixRes[$this->itemsKey] = array (
				'title' => $arrData[4][$key],
				'url' => $arrData[2][$key],
				'body' => '',
				'author' => $author[3],
				'category_title_main' => $author[7],
				'category_title_secondary' => $author[11],
			);
			$this->itemsKey++;
		}
		$this->_pageSettings['recall']['total_found'] = $this->itemsKey-1; 
	}

	private function getPageFromSession( &$mixRes ){
		if ( $this->_pageSettings['tags'] != $this->_filter['flg_language']."".$this->_filter['keywords']."".$this->_filter['category_id']) {
			$this->_pageSettings['tags'] = $this->_filter['flg_language']."".$this->_filter['keywords']."".$this->_filter['category_id'];
			$this->_pagedData = array();
		}
		$arrBook = array_chunk( $this->_pagedData,$this->_limit,TRUE );
		if ( !empty($arrBook[$this->_paging['curpage']-1]) ) {
			$this->_pageSettings['page'] = $this->_paging['curpage'];
			$this->_pageSettings['nextpage'] = $this->_paging['curpage']+1;
			$mixRes = $arrBook[$this->_paging['curpage']-1];
			return true;
		}
		return false;
	}

	public function getList( &$mixRes ) {
		$_withJson=$this->_withJson;
		if ( !empty( $this->_withIds ) ) {
			$this->_isNotEmpty=$this->getByIds( $mixRes );
			if(!empty($_withJson)){
				foreach( $mixRes as &$_item ){
					$_item['fields']=serialize($_item);
				}
			}
			return $this;
		}
		if( !empty( $this->_withPaging['page'] ) ){
			$this->_counter=( $this->_withPaging['page']-1 ) * $this->_limit;
			$_page=$this->_withPaging['page'];
		} else {
			$_page=( ($this->_counter+$this->_limit)/$this->_limit <= 1 )? 1 : (int)ceil(($this->_counter+$this->_limit)/$this->_limit);
		}
		$this->_paging=array( 'curpage'=>$_page );
		if ( $this->getPageFromSession( $mixRes) ) {
			$this->_isNotEmpty=!empty($mixRes);
			return $this;
		}
		$this->_isNotEmpty=$this->getByFilter( $mixRes );
		if( empty( $this->_withPaging ) && !empty( $mixRes ) ){
			$_arrKeys=array();
			foreach ( $mixRes as $key=>$content ) {
				if ( isset( $this->_pagedData[$key]['id'] ) ){
					$_arrKeys[]=$this->_pagedData[$key]['id'];
				}else{
					$_arrKeys[]=$key;
				}
			}
			$this->withIds( $_arrKeys )->getList( $mixRes );
		}
		if(!empty($this->_withJson)){
			foreach( $mixRes as &$_item ){
				$_item['fields']=serialize($_item);
			}
		}
		$this->init();
		return $this;
	}

	private function getUrl(){
		if ( $this->_filter['type'] == '0' ) {// search with keywords
			$searchUrlTail = "/find-articles.php?q=".str_replace(array(" ","'","(",")","\"","_"), "+", $this->_filter['keywords']);
		} else {
			$searchUrlTail = $this->_arrCategoriesUrl[$this->_filter['flg_language']][$this->_filter['category_id']];
		}
		// создадим ссылку на страницу нового контента
		if ( $this->_paging['curpage'] > 1 ) {
			if ( $this->_filter['type'] == '0' ) {// search with keywords
				return self::$flags[$this->_filter['flg_language']]['url'].$searchUrlTail."&page=".$this->_paging['curpage'];
			} else {
				return self::$flags[$this->_filter['flg_language']]['url'].$searchUrlTail.$this->_paging['curpage']."/";
			}
		} else {
			return self::$flags[$this->_filter['flg_language']]['url'].$searchUrlTail;
		}
	}

	public function prepareBody( &$mixRes ){
		foreach( $mixRes as $_i=>&$_item ){
			if( !is_array($_item) ){
				return;
			}
			$_fields=unserialize($_item['body']);

			if(empty($_fields)){
				continue;
			}
			if( $this->_withRewrite ){
				Zend_Registry::get('rewriter')->setText( $_fields['title'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['title']=(empty($_tmpRes))?$_fields['title']:array_shift( $_tmpRes );
				unset($_tmpRes);
				Zend_Registry::get('rewriter')->setText( $_fields['body'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['body']=(empty($_tmpRes))?$_fields['body']:array_shift( $_tmpRes );
			}
			if (empty($this->_filter['template'])) {
				$_item['body']=$_fields['body'];
				$_item['title']=$_fields['title'];
				continue;
			}
			ksort($_fields);
			ksort($this->_tags);
			$_tmpTemplate=$this->_filter['template'];
			$_replace=array_intersect_key( $_fields, $this->_tags );
			$_tmpTemplate=str_replace( $this->_tags, $_replace, $_tmpTemplate );
			$_item['body']=$_tmpTemplate;
		}
	}

	protected function assemblyQuery() {}

	public static function getInstance() {}

	// сброс настроек после выполнения
	protected function init() {
		$this->_withCategory==array();
		$this->_withKeywords=false;
		$this->_withJson=false;
		$this->_withRewrite=false;
	}

	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
		return $this;
	}

	public function withCategory( $_arrIds=array() ) {
		$this->_withCategory=$_arrIds;
		return $this;
	}

	public function withKeywords( $_arrIds=array() ) {
		$this->_withKeywords=$_arrIds;
		return $this;
	}
	/**
	* Фильтр для списка контента
	* $_obj->setFilter( $_GET['arrFlt'] )->getList( $mixRes )
	*
	* @param array $_arrFilter - поля и значения фильтра
	* @return object
	*/
	public function setFilter( $_arrFilter=array() ) {
		$this->_filter=$_arrFilter;
		$this
			->withCategory($_arrFilter['category_id'])
			->withKeywords($_arrFilter['keywords']);
		return $this;
	}

	/**
	* Ранее установленный фильтр для использования в шаблоне
	*
	* @param array $arrRes
	* @return object
	*/
	public function getFilter( &$arrRes ) {
		$arrRes = $this->_filter;
		return $this;
	}

	/**
	 * Сколько контента вернуть
	 *
	 * @param  $_intLimit
	 * @return object
	 */
	public function setLimited( $_intLimit ) {
		$this->_limit=$_intLimit;
		return $this;
	}

	/**
	 * Счетчик контента запощеного в проект от начала. Используется для внешних источников, те которые не име
	 *
	 * @param  $_intCounter
	 * @return object
	 */
	public function setCounter( $_intCounter ) {
		$this->_counter=$_intCounter;
		return $this;
	}

	/**
	 * Дополнительные данные для генерации формы на шаблоне адаптера
	 * Нужно быть остарожным чтобы не обнулить массив который выкидывается на шаблон
	 *
	 * @param array $arrRes
	 * @return object
	 */
	public function getAdditional( &$arrRes ) {
		$this->_res = $arrRes;
		return $this;
	}

	public function setPost( $_arrPost=array() ){
		$this->_post=$_arrPost;
		return $this;
	}

	public function getResult( &$arrRes ){
		if (!empty( $this->_post['arrCnt'][4]['settings'] ) ) {
			$arrFacility = array ();
			switch ( $this->_post['arrCnt'][4]['settings']['flg_language'] ) {
				case '':
				case '0':
					$arrRes = array();
					return $this;
				case '1':
					$category = new Core_Category( 'Articles' );
					break;
				case '2':
				case '3':
				case '4':
					$category = new Core_Category( 'Articles_'.self::$flags[$this->_post['arrCnt'][4]['settings']['flg_language']]['tail'] );
					break;
			}
			$category->getLevel( $arrFacility['arrCategories'], $this->_res );
			$category->getTree( $arrFacility['arrTree'] );
			$arrRes[4] = $arrFacility;
			return true;
		}
		return false;
	}

	public function withTags( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_withTags=$_str;
		return $this;
	}

	public function withPaging( $_arr=array() ) {
		$this->_withPaging=$_arr;
		if ( empty($this->_withPaging['page']) )
			$this->_withPaging['page'] = 1;
		return $this;
	}

	public function getPaging( &$arrRes ) {
		$arrRes = array ( 'num' => array (),
			'recall' => $this->_pageSettings['recall']['total_found']
		);
		if( $this->_paging['curpage']>1){
			$arrRes['urlminus']='/?page='.($this->_paging['curpage']-1);
			$arrRes['num'][]=array(
				'number'=>($this->_paging['curpage']-1),
				'url'=>'./?page='.($this->_paging['curpage']-1)
			);
		}
		$arrRes['num'][] = array (
			'sel' => 1,
			'number' => $this->_paging['curpage']
		);
		$arrRes['num'][] = array (
			'number' => $this->_paging['curpage']+1,
			'url'=> './?page='.($this->_paging['curpage']+1)
		);
		$arrRes['urlmin']='/?page=1';
		$arrRes['urlplus']='/?page='.($this->_paging['curpage']+1);
		$this->_paging=array();
		return $this;
	}

	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_filter=$arrSettings;
		return $this;
	}

	public function withIds( $_arrIds=array() ){
		$this->_withIds=$_arrIds;
		return $this;
	}

	public function checkEmpty(){
		return $this->_isNotEmpty;
	}
	
	public function setFile( $_arrFile=array() ){
		return $this;
	}
}
?>