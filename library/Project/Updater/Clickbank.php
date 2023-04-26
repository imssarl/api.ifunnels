<?php

class Project_Updater_Clickbank extends Core_Updater_Abstract {

	public function update( Core_Updater $obj ) {
	$text_sp="<document>
		<h3>Arte y entretenimiento</h3><ul><li>Arquitectura</li><li>Arte</li><li>Arte corporal</li><li>Danza</li><li>Moda</li><li>Cine y television</li><li>General</li><li>Humor</li><li>Trucos de magia</li><li>Musica</li><li>Fotografia</li><li>Radio</li><li>Teatro</li></ul>
		<h3>Apuestas</h3><ul><li>Juegos de mesa de casino</li><li>Futbol (americano)</li><li>General</li><li>Carreras de caballos</li><li>Loteria</li><li>Poker</li><li>Futbol</li></ul>
		<h3>Negocios/Inversiones</h3><ul><li>Carreras, industrias y profesiones</li><li>Productos</li><li>Deudas</li><li>Derivados</li><li>Economia</li><li>Patrimonio neto</li><li>Cambio de divisas</li><li>General</li><li>Negocios internacionales</li><li>Administracion y liderazgo</li><li>Comercializacion y ventas</li><li>Subcontratacion</li><li>Finanzas personales</li><li>Bienes raices</li><li>Pequenas empresas/Iniciativa empresarial</li></ul>
		<h3>Computadoras/Internet</h3><ul><li>Bases de datos</li><li>Servicios de correo electronico</li><li>General</li><li>Graficos</li><li>Hardware</li><li>Redes</li><li>Sistemas operativos</li><li>Programacion</li><li>Software</li><li>Administracion del sistema</li><li>Diseno y analisis del sistema</li><li>Alojamiento web</li><li>Diseno de sitios web</li></ul>
		<h3>Cocina, comidas y vinos</h3><ul><li>Pasteleria</li><li>Parrillada</li><li>Cocina</li><li>Bebidas y tragos</li><li>General</li><li>Recetas</li><li>Regional e internacional</li><li>Dieta especial</li><li>Ocasiones especiales</li><li>Vegetales/Comida vegetariana</li><li>Vinicultura</li></ul>
		<h3>Negocios y comercializacion electronicos</h3><ul><li>Practicas de comercializacion del afiliado</li><li>Comercializacion de articulos</li><li>Subasta</li><li>Encabezados</li><li>Comercializacion en blogs</li><li>Publicidades clasificadas</li><li>Asesoramiento</li><li>Redaccion de textos publicitarios</li><li>Dominios</li><li>Operaciones de comercio electronico</li><li>Estrategias de revistas electronicas</li><li>Comercializacion electronica</li><li>General</li><li>Estudio de mercado</li><li>Comercializacion</li><li>Comercializacion por nicho</li><li>Encuestas pagas</li><li>Publicidad de pago por clic</li><li>Promocion</li><li>SEM y SEO</li><li>Comercializacion a traves de medios sociales</li><li>Emisores</li><li>Comercializacion por video</li></ul><h3>Educacion</h3><ul><li>Admisiones</li><li>Materiales educativos</li><li>Educacion superior</li><li>Educacion primaria y secundaria</li><li>Prestamos para estudiantes</li><li>Guias de estudio y preparacion para examenes</li></ul>
		<h3>Empleos y trabajo</h3><ul><li>Guias para elaborar curriculum y cartas de presentacion</li><li>General</li><li>Listados de trabajos</li><li>Guias para la busqueda de trabajo</li><li>Capacitacion para el trabajo</li></ul>
		<h3>Ficcion</h3><ul><li>General</li></ul>
		<h3>Juegos</h3><ul><li>Reparaciones y guias para consolas</li><li>General</li><li>Guias de estrategia</li></ul>
		<h3>Productos ecologicos</h3><ul><li>Energia alternativa</li><li>Preservacion y eficiencia</li><li>General</li></ul>
		<h3>Salud y estado fisico</h3><ul><li>Adicciones</li><li>Belleza</li><li>Salud dental</li><li>Dietas y disminucion de peso</li><li>Ejercitacion y estado fisico</li><li>General</li><li>Meditacion</li><li>Salud mental</li><li>Salud del hombre</li><li>Nutricion</li><li>Remedios</li><li>Dormir y sonar</li><li>Salud espiritual</li><li>Entrenamiento para aumentar la fuerza</li><li>Salud de la mujer</li><li>Yoga</li></ul>
		<h3>Hogar y jardin</h3><ul><li>Cuidado de animales y mascotas</li><li>Manualidades y pasatiempos</li><li>Entretenimiento</li><li>Jardineria y horticultura</li><li>General</li><li>Compra de inmuebles</li><li>Mejoras en el hogar y consejos practicos</li><li>Diseno de interiores</li><li>Costura</li><li>Bodas</li></ul>
		<h3>Idiomas</h3><ul><li>Arabe</li><li>Chino</li><li>Ingles</li><li>Frances</li><li>Aleman</li><li>Hebreo</li><li>Hindi</li><li>Italiano</li><li>Japones</li><li>Otro</li><li>Ruso</li><li>Lenguaje de senas</li><li>Espanol</li><li>Tailandes</li></ul>
		<h3>Movil</h3><ul><li>Aplicaciones</li><li>Herramientas para programadores</li><li>General</li><li>Tonos de llamada</li><li>Seguridad</li><li>Video</li></ul>
		<h3>Crianza y familia</h3><ul><li>Divorcio</li><li>Educacion</li><li>Genealogia</li><li>General</li><li>Matrimonio</li><li>Crianza</li><li>Embarazo y nacimiento</li><li>Necesidades especiales</li></ul>
		<h3>Politica/Actualidad</h3><ul><li>General</li></ul>
		<h3>Referencias</h3><ul><li>Industria automotriz</li><li>Catalogos y directorios</li><li>Guias para consumidores</li><li>Educacion</li><li>Etiqueta</li><li>Gays/Lesbianas</li><li>General</li><li>Leyes y asuntos legales</li><li>Las ciencias</li><li>Redaccion</li></ul>
		<h3>Autoayuda</h3><ul><li>Maltratos</li><li>Trastornos alimenticios</li><li>Guias de citas</li><li>General</li><li>Matrimonio y relaciones</li><li>Motivacion/Transformacional</li><li>Finanzas personales</li><li>Oratoria</li><li>Defensa personal</li><li>Autoestima</li><li>Control del estres</li><li>Exito</li><li>Administracion del tiempo</li></ul>
		<h3>Software y servicios</h3><ul><li>Software contra aplicaciones espia y anuncios</li><li>Investigaciones de antecedentes</li><li>Comunicaciones</li><li>Citas</li><li>Herramientas de desarrollo</li><li>Fotografias digitales</li><li>Controladores</li><li>Educacion</li><li>Correo electronico</li><li>Inversiones en monedas extranjeras</li><li>General</li><li>Diseno grafico</li><li>Alojamiento web</li><li>Herramientas de Internet</li><li>MP3 y audio</li><li>Redes</li><li>Sistemas operativos</li><li>Otros programas de software para inversiones</li><li>Finanzas personales</li><li>Productividad</li><li>Limpiadores de registro</li><li>Busqueda telefonica inversa</li><li>Protectores y fondos de pantallas</li><li>Seguridad</li><li>Optimizacion del sistema</li><li>Herramientas</li><li>Video</li><li>Diseno web</li></ul><h3>Espiritualidad, Nueva Era y creencias alternativas</h3><ul><li>Astrologia</li><li>General</li><li>Hipnosis</li><li>Magia</li><li>Numerologia</li><li>Paranormal</li><li>Parapsicologia</li><li>Religion</li><li>Tarot</li><li>Hechiceria</li></ul><h3>Deportes</h3><ul><li>Automovilismo</li><li>Beisbol</li><li>Basquetbol</li><li>Preparacion</li><li>Ciclismo</li><li>Deportes extremos</li><li>Futbol americano</li><li>General</li><li>Golf</li><li>Hockey</li><li>Deportes individuales</li><li>Artes marciales</li><li>Montanismo</li><li>Otros deportes de equipo</li><li>Naturaleza y aire libre</li><li>Deportes con raquetas</li><li>Correr</li><li>Futbol</li><li>Softball</li><li>Entrenamiento</li><li>Voleibol</li><li>Deportes acuaticos</li><li>Deportes de invierno</li></ul><h3>Viajes</h3><ul><li>Africa</li><li>Asia</li><li>Canada</li><li>Caribe</li><li>Europa</li><li>General</li><li>Latinoamerica</li><li>Medio Oriente</li><li>Viajes especiales</li><li>Estados Unidos</li></ul></document>";
	$text_du="<document><h3>Kunst &amp; Unterhaltung</h3><ul><li>Architektur</li><li>Kunst</li><li>Korperkunst</li><li>Tanz</li><li>Mode</li><li>Film &amp; TV</li><li>Allgemein</li><li>Humor</li><li>Zaubertricks</li><li>Musik</li><li>Fotografie</li><li>Radio</li><li>Theater</li></ul>
		<h3>Wetten</h3><ul><li>Casino-Tischspiele</li><li>Football (American)</li><li>Allgemein</li><li>Pferderennen</li><li>Lotto</li><li>Poker</li><li>Futball</li></ul>
		<h3>Unternehmen / Investitionen</h3><ul><li>Laufbahn, Branchen &amp; Berufe</li><li>Verbrauchsguter</li><li>Fremdkapital</li><li>Derivate</li><li>Wirtschaft</li><li>Stammaktien &amp; Wertpapiere</li><li>Devisen</li><li>Allgemein</li><li>Internationale Geschafte</li><li>Management &amp; Fuhrung</li><li>Marketing &amp; Vertrieb</li><li>Outsourcing</li><li>Private Finanzen</li><li>Immobilien</li><li>Mittelstand/Unternehmer</li></ul>
		<h3>Computer/Internet</h3><ul><li>Datenbanken</li><li>E-Mail-Dienste</li><li>Allgemein</li><li>Grafik</li><li>Hardware</li><li>Netzwerke</li><li>Betriebssysteme</li><li>Programmierung</li><li>Software</li><li>Systemadministration</li><li>Systemanalyse &amp; -design</li><li>Web-Hosting</li><li>Website-Design</li></ul>
		<h3>Kochen, Speisen &amp; Wein</h3><ul><li>Backen</li><li>Grillen</li><li>Kochen</li><li>Getranke</li><li>Allgemein</li><li>Rezepte</li><li>Regional &amp; Intern.</li><li>Spezialdiaten</li><li>Besondere Anlasse</li><li>Gemuse/Vegetarisch</li><li>Weinherstellung</li></ul>
		<h3>E-Business &amp; E-Marketing</h3><ul><li>Affiliate-Marketing</li><li>Artikelmarketing</li><li>Auktionen</li><li>Banner</li><li>Blog-Marketing</li><li>Anzeigenwerbung</li><li>Beratung</li><li>Texten</li><li>Domanen</li><li>E-Commerce-Aktivitaten</li><li>Onlinemagazin-Strategien</li><li>E-Mail-Marketing</li><li>Allgemein</li><li>Marktforschung</li><li>Marketing</li><li>Nischenmarketing</li><li>Bezahlte Umfragen</li><li>Pay-per-click-Werbung</li><li>Promotion</li><li>Suchmaschinenmarketing &amp; -optimierung</li><li>Social Media Marketing</li><li>Antragsteller</li><li>Videomarketing</li></ul>
		<h3>Bildung</h3><ul><li>Zulassungen</li><li>Lehrmaterialien</li><li>Hochschulwesen</li><li>Kindergarten bis 12. Schuljahr</li><li>Studiendarlehen</li><li>Studium/Prufungsvorbereitung &#8211; Anleitungen</li></ul>
		<h3>Beschaftigung &amp; Jobs</h3><ul><li>Anschreiben/Lebenslauf &#8211; Leitfaden</li><li>Allgemein</li><li>Stellenanzeigen</li><li>Stellensuche &#8211; Leitfaden</li><li>Qualifikationen/Schulungen</li></ul>
		<h3>Belletristik</h3><ul><li>Allgemein</li></ul>
		<h3>Spiele</h3><ul><li>Konsole &#8211; Anleitungen/Reparaturen</li><li>Allgemein</li><li>Strategie &#8211; Anleitungen</li></ul>
		<h3>Bioprodukte</h3><ul><li>Alternative Energie</li><li>Umweltschutz &amp; Effizienz</li><li>Allgemein</li></ul>
		<h3>Gesundheit &amp; Fitness</h3><ul><li>Suchtprobleme</li><li>Schonheit</li><li>Zahnmedizin</li><li>Diat &amp; Abnehmen</li><li>Bewegung &amp; Fitness</li><li>Allgemein</li><li>Meditation</li><li>Psychische Gesundheit</li><li>Gesundheit des Mannes</li><li>Ernahrung</li><li>Heilmittel</li><li>Schlafen und Traume</li><li>Geistige Gesundheit</li><li>Kraftsport</li><li>Gesundheit der Frau</li><li>Yoga</li></ul>
		<h3>Haus &amp; Garten</h3><ul><li>Tierpflege &amp; Haustiere</li><li>Handwerk &amp; Hobbys</li><li>Unterhaltung</li><li>Gartenarbeit &amp; Gartenbau</li><li>Allgemein</li><li>Haus-/Wohnungskauf</li><li>Anleitungen &amp; Renovierung</li><li>Innenarchitektur</li><li>Naharbeiten</li><li>Hochzeiten</li></ul>
		<h3>Sprachen</h3><ul><li>Arabisch</li><li>Chinesisch</li><li>Englisch</li><li>Franzosisch</li><li>Deutsch</li><li>Hebraisch</li><li>Hindi</li><li>Italienisch</li><li>Japanisch</li><li>Andere</li><li>Russisch</li><li>Gebardensprache</li><li>Spanisch</li><li>Thai</li></ul>
		<h3>Mobilfunk</h3><ul><li>Apps</li><li>Entwickler-Tools</li><li>Allgemein</li><li>Klingeltone</li><li>Sicherheit</li><li>Video</li></ul>
		<h3>Erziehung &amp; Familie</h3><ul><li>Scheidung</li><li>Bildung</li><li>Ahnenforschung</li><li>Allgemein</li><li>Trauung</li><li>Erziehung</li><li>Schwangerschaft &amp; Geburt</li><li>Sonderpadagogik</li></ul>
		<h3>Politik/Zeitgeschehen</h3><ul><li>Allgemein</li></ul>
		<h3>Nachschlagewerke</h3><ul><li>Eigenantrieb</li><li>Kataloge &amp; Worterbucher</li><li>Verbraucherfuhrer</li><li>Padagogik</li><li>Etikette</li><li>Homosexualitat</li><li>Allgemein</li><li>Recht &amp; Gesetz</li><li>Die Wissenschaften</li><li>Schreiben</li></ul>
		<h3>Selbsthilfe</h3><ul><li>Missbrauch</li><li>Essstorungen</li><li>Beziehungstipps</li><li>Allgemein</li><li>Heirat &amp; Partnerschaft</li><li>Motivation/Transformation</li><li>Private Finanzen</li><li>Offentliches Reden</li><li> Selbstverteidigung</li><li>Selbstwertgefuhl</li><li>Stressbewaltigung</li><li>Erfolg</li><li>Zeitmanagement</li></ul>
		<h3>Software &amp; Services</h3><ul><li>Anti-Adware/Spyware</li><li>Hintergrundrecherche</li><li>Kommunikation</li><li>Dating</li><li>Entwickler-Tools</li><li>Digitalfotos</li><li>Treiber</li><li>Bildung</li><li>E-Mail</li><li>Devisenmarktinvestitionen</li><li>Allgemein</li><li>Grafikdesign</li><li>Hosting</li><li>Internet-Tools</li><li>MP3 &amp; Audio</li><li>Netzwerke</li><li>Betriebssysteme</li><li>Andere Investment-Software</li><li>Private Finanzen</li><li>Produktivitat</li><li>Registry Cleaner</li><li>Telefonbuch &#8211; Umkehrsuche</li><li>Bildschirmschoner &amp; -hintergrund</li><li>Sicherheit</li><li>Systemoptimierung</li><li>Dienstprogramme</li><li>Video</li><li>Webdesign</li></ul><h3>Spiritualitat, New Age &amp; alternativer Glaube</h3><ul><li>Astrologie</li><li>Allgemein</li><li>Hypnose</li><li>Magie</li><li>Numerologie</li><li>Ubersinnliches</li><li>Parapsychologie</li><li>Religion</li><li>Tarot</li><li>Hexerei</li></ul><h3>Sport</h3><ul><li>Auto</li><li>Baseball</li><li>Basketball</li><li>Coaching</li><li>Radfahren</li><li>Extremsport</li><li>Football</li><li>Allgemein</li><li>Golf</li><li>Hockey</li><li>Individualsport</li><li>Kampfsport</li><li>Bergsteigen</li><li>Andere Teamsportarten</li><li>Outdoor &amp; Natur</li><li>Schlagersportarten</li><li>Laufen</li><li>Futball</li><li>Softball</li><li>Training</li><li>Volleyball</li><li>Wassersport</li><li>Wintersport</li></ul><h3>Reisen</h3><ul><li>Afrika</li><li>Asien</li><li>Kanada</li><li>Karibik</li><li>Europa</li><li>Allgemein</li><li>Lateinamerika</li><li>Nahost</li><li>Spezialreisen</li><li>USA</li></ul></document>";
	$text_fr="<document><h3>Arts &amp; divertissements</h3><ul><li>Architecture</li><li>Art</li><li>Art corporel</li><li>Danse</li><li>Mode</li><li>Film &amp; television</li><li>General</li><li>Humour</li><li>Tour de magie</li><li>Musique</li><li>Photographie</li><li>Radio</li><li>Theatre</li></ul>
		<h3>Pari</h3><ul><li>Jeux de casino</li><li>Football americain</li><li>General</li><li>Course de chevaux</li><li>Loterie</li><li>Poker</li><li>Football</li></ul>
		<h3>Affaires/Investissement</h3><ul><li>Carrieres, industries &amp; professions</li><li>Produits de base</li><li>Dette</li><li>Derives</li><li>Economie</li><li>Actions &amp; Stocks</li><li>Marche international des devises</li><li>General</li><li>Commerce international</li><li>Gestion &amp; leadership</li><li>Marketing &amp; ventes</li><li>Delocalisation</li><li>Finance personnelle</li><li>Immobilier</li><li>PME/Entreprenariat</li></ul>
		<h3>Informatique/Internet</h3><ul><li>Bases de donnees</li><li>Services courriel</li><li>General</li><li>Graphiques</li><li>Materiel</li><li>Reseau</li><li>Systemes d&#8217;exploitation</li><li>Programmation</li><li>Logiciel</li><li>Administration systeme</li><li>Analyse &amp; conception systeme</li><li>Hebergement web</li><li>Conception de site web</li></ul>
		<h3>Cuisine, nourriture &amp; vin</h3><ul><li>Cuisson au four</li><li>Barbecue</li><li>La cuisine</li><li>Cocktails &amp; boissons</li><li>General</li><li>Recettes</li><li>Regional &amp; international</li><li>Regime special</li><li>Grandes Occasions</li><li>Legumes/Vegetarien</li><li>Oenologie</li></ul>
		<h3>Commerce electronique &amp; marketing sur Internet</h3><ul><li>Marketing d&#8217;affiliation</li><li>Article marketing</li><li>Encheres</li><li>Bannieres</li><li>Blog marketing</li><li>Petites annonces</li><li>Consultant</li><li>Redaction publicitaire</li><li>Domaines</li><li>Commerce electronique</li><li>Strategies de magazine electronique</li><li>Marketing par courriel</li><li>General</li><li>Recherche de marche</li><li>Marketing</li><li>Marketing de creneaux</li><li>Enquete payee</li><li>Publicite par paiement au clic</li><li>Promotion</li><li>Marketing pour les moteurs de recherche (SEM) &amp; optimisation pour les moteurs de recherche (SEO)</li><li>Marketing des medias sociaux</li><li>Promoteurs</li><li>Video marketing</li></ul><h3>Education</h3><ul><li>Admissions</li><li>Materiel pedagogique</li><li>Enseignement Superieur</li><li>Maternelle a Terminale</li><li>Prets etudiants</li><li>Test preparatoire &amp; Guides d&#8217;etudes</li></ul><h3>Emploi &amp; travail</h3><ul><li>Manuels pour CV et Lettre d&#8217;accompagnement</li><li>General</li><li>Annonces d&#8217;emploi</li><li>Guides de recherche d&#8217;emploi</li><li>Competences/Formation</li></ul><h3>Fiction</h3><ul><li>General</li></ul><h3>Jeux</h3><ul><li>Guides &amp; reparations des consoles</li><li>General</li><li>Guides de strategie</li></ul><h3>Produits ecologiques</h3><ul><li>Energie alternative</li><li>Conservation &amp; efficacite</li><li>General</li></ul><h3>Sante &amp; remise en forme</h3><ul><li>Toxicomanies</li><li>Beaute</li><li>Hygiene dentaire</li><li>Regimes &amp; perte de poids</li><li>Exercice &amp; remise en forme</li><li>General</li><li>Meditation</li><li>Sante, corps &amp; esprit</li><li>Bien-etre masculin</li><li>Nutrition</li><li>Remedes</li><li>Sommeil et reves</li><li>Sante spirituelle</li><li>Musculation</li><li>Bien-etre feminin</li><li>Yoga</li></ul><h3>Maison &amp; jardin</h3><ul><li>Soins des animaux &amp; animaux domestiques</li><li>Artisanat &amp; loisirs</li><li>Divertissement</li><li>Jardinage &amp; horticulture</li><li>General</li><li>Achat de maison</li><li>Bricolage</li><li>Decoration interieure</li><li>Couture</li><li>Mariages</li></ul><h3>Langues</h3><ul><li>Arabe</li><li>Chinois</li><li>Anglais</li><li>Francais</li><li>Allemand</li><li>Hebreu</li><li>Hindi</li><li>Italien</li><li>Japonais</li><li>Autres</li><li>Russe</li><li>Langue des signes</li><li>Espagnol</li><li>Thai</li></ul><h3>Telephone portable</h3><ul><li>Applications</li><li>Outils du developpeur</li><li>General</li><li>Sonneries</li><li>Securite</li><li>Video</li></ul><h3>Education des enfants &amp; familles</h3><ul><li>Divorce</li><li>Education</li><li>Genealogie</li><li>General</li><li>Mariage</li><li>Education des enfants</li><li>Grossesse &amp; accouchement</li><li>Besoins speciaux</li></ul><h3>Politique/Actualite</h3><ul><li>General</li></ul><h3>References</h3><ul><li>Automobile</li><li>Catalogues &amp; repertoires</li><li>Guides du consommateur</li><li>Education</li><li>Etiquette</li><li>Gay/Lesbienne</li><li>General</li><li>Droit &amp; Questions juridiques</li><li>Les sciences naturelles</li><li>Redaction</li></ul><h3>Auto-assistance</h3><ul><li>Maltraitance</li><li>Guides de rencontre</li><li>Trouble de l&#8217;alimentation</li><li>General</li><li>Conseils matrimoniaux &amp; Relations</li><li>Motivation/Transformation</li><li>Finance personnelle</li><li>Parler en public</li><li>Self Defense</li><li>Estime de soi</li><li>Gestion du stress</li><li>Reussite</li><li>Gestion du temps</li></ul><h3>Logiciel &amp; Services</h3><ul><li>Logiciel anti-publicite/logiciel espion</li><li>Enquetes sur le passe</li><li>Communications</li><li>Datation</li><li>Outils du developpeur</li><li>Photos numeriques</li><li>Pilotes</li><li>Education</li><li>Courriel</li><li>Investissement dans le marche des changes</li><li>General</li><li>Infographie</li><li>Hebergement</li><li>Outils Internet</li><li>MP3 &amp; audio</li><li>Reseau</li><li>Systemes d&#8217;exploitation</li><li>Autre investissement logiciel</li><li>Finance personnelle</li><li>Productivite</li><li>Nettoyeurs de registre</li><li>Annuaire inverse pour numero de telephone</li><li>Ecrans de veille &amp; papier peint</li><li>Securite</li><li>Optimisation du systeme</li><li>Utilitaires</li><li>Video</li><li>Conception web</li></ul><h3>Spiritualite, New Age &amp; croyances alternatives</h3><ul><li>Astrologie</li><li>General</li><li>Hypnose</li><li>Magie</li><li>Numerologie</li><li>Paranormal</li><li>Medium</li><li>Religion</li><li>Tarot</li><li>Sorcellerie</li></ul><h3>Sports</h3><ul><li>Automobile</li><li>Base-ball</li><li>Basket-ball</li><li>Coaching</li><li>Cyclisme</li><li>Sports extremes</li><li>Football americain</li><li>General</li><li>Golf</li><li>Hockey</li><li>Sports individuels</li><li>Arts martiaux</li><li>Escalade</li><li>Autres sports d&#8217;equipe</li><li>De plein air &amp; nature</li><li>Sports de raquettes</li><li>Course a pied</li><li>Football</li><li>Softball</li><li>Entrainement</li><li>Volley-ball</li><li>Sports nautiques</li><li>Sports d&#8217;hiver</li></ul><h3>Voyage</h3><ul><li>Afrique</li><li>Asie</li><li>Canada</li><li>Caraibes</li><li>Europe</li><li>General</li><li>Amerique latine</li><li>Moyen-Orient</li><li>Voyage specialise</li><li>Etats-Unis</li></ul></document>";
	$text_en="<document><h3>Arts &amp; Entertainment</h3><ul><li>Architecture</li><li>Art</li><li>Body Art</li><li>Dance</li><li>Fashion</li><li>Film &amp; Television</li><li>General</li><li>Humor</li><li>Magic Tricks</li><li>Music</li><li>Photography</li><li>Radio</li><li>Theater</li></ul>
		<h3>Betting</h3><ul><li>Casino Table Games</li><li>Football (American)</li><li>General</li><li>Horse Racing</li><li>Lottery</li><li>Poker</li><li>Soccer</li></ul>
		<h3>Business/Investing</h3><ul><li>Careers, Industries &amp; Professions</li><li>Commodities</li><li>Debt</li><li>Derivatives</li><li>Economics</li><li>Equities &amp; Stocks</li><li>Foreign Exchange</li><li>General</li><li>International Business</li><li>Management &amp; Leadership</li><li>Marketing &amp; Sales</li><li>Outsourcing</li><li>Personal Finance</li><li>Real Estate</li><li>Small Biz / Entrepreneurship</li></ul>
		<h3>Computers/Internet</h3><ul><li>Databases</li><li>Email Services</li><li>General</li><li>Graphics</li><li>Hardware</li><li>Networking</li><li>Operating Systems</li><li>Programming</li><li>Software</li><li>System Administration</li><li>System Analysis &amp; Design</li><li>Web Hosting</li><li>Web Site Design</li></ul>
		<h3>Cooking, Food &amp; Wine</h3><ul><li>Baking</li><li>BBQ</li><li>Cooking</li><li>Drinks &amp; Beverages</li><li>General</li><li>Recipes</li><li>Regional &amp; Intl.</li><li>Special Diet</li><li>Special Occasions</li><li>Vegetables / Vegetarian</li><li>Wine Making</li></ul>
		<h3>E-business &amp; E-marketing</h3><ul><li>Affiliate Marketing</li><li>Article Marketing</li><li>Auctions</li><li>Banners</li><li>Blog Marketing</li><li>Classified Advertising</li><li>Consulting</li><li>Copywriting</li><li>Domains</li><li>E-commerce Operations</li><li>E-zine Strategies</li><li>Email Marketing</li><li>General</li><li>Market Research</li><li>Marketing</li><li>Niche Marketing</li><li>Paid Surveys</li><li>Pay Per Click Advertising</li><li>Promotion</li><li>SEM &amp; SEO</li><li>Social Media Marketing</li><li>Submitters</li><li>Video Marketing</li></ul>
		<h3>Education</h3><ul><li>Admissions</li><li>Educational Materials</li><li>Higher Education</li><li>K-12</li><li>Student Loans</li><li>Test Prep &amp; Study Guides</li></ul>
		<h3>Employment &amp; Jobs</h3><ul><li>Cover Letter &amp; Resume Guides</li><li>General</li><li>Job Listings</li><li>Job Search Guides</li><li>Job Skills / Training</li></ul>
		<h3>Fiction</h3><ul><li>General</li></ul>
		<h3>Games</h3><ul><li>Console Guides &amp; Repairs</li><li>General</li><li>Strategy Guides</li></ul>
		<h3>Green Products</h3><ul><li>Alternative Energy</li><li>Conservation &amp; Efficiency</li><li>General</li></ul>
		<h3>Health &amp; Fitness</h3><ul><li>Addiction</li><li>Beauty</li><li>Dental Health</li><li>Diets &amp; Weight Loss</li><li>Exercise &amp; Fitness</li><li>General</li><li>Meditation</li><li>Men&#8217;s Health</li><li>Mental Health</li><li>Nutrition</li><li>Remedies</li><li>Sleep &amp; Dreams</li><li>Spiritual Health</li><li>Strength Training</li><li>Women&#8217;s Health</li><li>Yoga</li></ul><h3>Home &amp; Garden</h3><ul><li>Animal Care &amp; Pets</li><li>Crafts &amp; Hobbies</li><li>Entertaining</li><li>Gardening &amp; Horticulture</li><li>General</li><li>Homebuying</li><li>How-to &amp; Home Improvements</li><li>Interior Design</li><li>Sewing</li><li>Weddings</li></ul><h3>Languages</h3><ul><li>Arabic</li><li>Chinese</li><li>English</li><li>French</li><li>German</li><li>Hebrew</li><li>Hindi</li><li>Italian</li><li>Japanese</li><li>Other</li><li>Russian</li><li>Sign Language</li><li>Spanish</li><li>Thai</li></ul><h3>Mobile</h3><ul><li>Apps</li><li>Developer Tools</li><li>General</li><li>Ringtones</li><li>Security</li><li>Video</li></ul><h3>Parenting &amp; Families</h3><ul><li>Divorce</li><li>Education</li><li>Genealogy</li><li>General</li><li>Marriage</li><li>Parenting</li><li>Pregnancy &amp; Childbirth</li><li>Special Needs</li></ul><h3>Politics/ Current Events</h3><ul><li>General</li></ul><h3>Reference</h3><ul><li>Automotive</li><li>Catalogs &amp; Directories</li><li>Consumer Guides</li><li>Education</li><li>Etiquette</li><li>Gay / Lesbian</li><li>General</li><li>Law &amp; Legal Issues</li><li>The Sciences</li><li>Writing</li></ul><h3>Self-Help</h3><ul><li>Abuse</li><li>Dating Guides</li><li>Eating Disorders</li><li>General</li><li>Marriage &amp; Relationships</li><li>Motivational / Transformational</li><li>Personal Finance</li><li>Public Speaking</li><li>Self Defense</li><li>Self-Esteem</li><li>Stress Management</li><li>Success</li><li>Time Management</li></ul><h3>Software &amp; Services</h3><ul><li>Anti Adware / Spyware</li><li>Background Investigations</li><li>Communications</li><li>Dating</li><li>Developer Tools</li><li>Digital Photos</li><li>Drivers</li><li>Education</li><li>Email</li><li>Foreign Exchange Investing</li><li>General</li><li>Graphic Design</li><li>Hosting</li><li>Internet Tools</li><li>MP3 &amp; Audio</li><li>Networking</li><li>Operating Systems</li><li>Other Investment Software</li><li>Personal Finance</li><li>Productivity</li><li>Registry Cleaners</li><li>Reverse Phone Lookup</li><li>Screensavers &amp; Wallpaper</li><li>Security</li><li>System Optimization</li><li>Utilities</li><li>Video</li><li>Web Design</li></ul><h3>Spirituality, New Age &amp; Alternative Beliefs</h3><ul><li>Astrology</li><li>General</li><li>Hypnosis</li><li>Magic</li><li>Numerology</li><li>Paranormal</li><li>Psychics</li><li>Religion</li><li>Tarot</li><li>Witchcraft</li></ul><h3>Sports</h3><ul><li>Auto</li><li>Baseball</li><li>Basketball</li><li>Coaching</li><li>Cycling</li><li>Extreme Sports</li><li>Football</li><li>General</li><li>Golf</li><li>Hockey</li><li>Individual Sports</li><li>Martial Arts</li><li>Mountaineering</li><li>Other Team Sports</li><li>Outdoors &amp; Nature</li><li>Racket Sports</li><li>Running</li><li>Soccer</li><li>Softball</li><li>Training</li><li>Volleyball</li><li>Water Sports</li><li>Winter Sports</li></ul><h3>Travel</h3><ul><li>Africa</li><li>Asia</li><li>Canada</li><li>Caribbean</li><li>Europe</li><li>General</li><li>Latin America</li><li>Middle East</li><li>Specialty Travel</li><li>United States</li></ul></document>";

	$sql="CREATE TABLE IF NOT EXISTS `category_clickbank_tree` (
		`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`pid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
		`level` INT(11) UNSIGNED NOT NULL DEFAULT '0',
		`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
		`priority` SMALLINT(3) UNSIGNED NOT NULL DEFAULT '100',
		`title` VARCHAR(255) NOT NULL DEFAULT '',
		PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM
		ROW_FORMAT=DEFAULT
		AUTO_INCREMENT=0";

	Core_Sql::setExec($sql);
	
	$_tableName = 'category_types';
	$_tableData = array('type' => 'nested',
						'flg_sort' => 0,
						'flg_typelink' => 0,
						'flg_multilng' => 0,
						'flg_deflng' => 0,
						'title' => 'Clickbank',
						'storage' => 'category_clickbank_tree',
						'description' => 'clicbank category',
						);

	$field = Core_Sql::getRecord("SELECT * FROM ".$_tableName." WHERE title = 'Clickbank'");
	if ( empty( $field ) ) {
		
		$_idInput=Core_Sql::setInsert($_tableName,$_tableData);
		
		$this->initCat($text_en);
		
		Core_Sql::setUpdate($_tableName,array('id' => $_idInput, 'flg_multilng' => 1,'flg_deflng' => 1));
		
		$this->initLang($text_fr,$text_sp,$text_du);
	echo('Records Clicbank created!');
	}else{
	echo('Base have Clicbank record!');exit();
	}
	
	return true;
	}
	
	public $_first;
	public $_second;
	
	public function getFirstSecond($text) {

		$data = @simplexml_load_string( $text );

		for ($i=0;isset($data->h3[$i]);$i++) {
			$title = "{$data->h3[$i]}";
			$this->_first[] = $title;
			for ($k=0;isset($data->ul[$i]->li[$k]);$k++) {
				$subtitle = "{$data->ul[$i]->li[$k]}";
				$this->_second[$title][] = $subtitle;
			}
		}
	return true;
	}
	
	public function initCat($text) {

		
		if ( $this->getFirstSecond($text) ) {
		
			$_model=new Core_Category( 'Clickbank' );
			if ( !$_model->setPid()->setData( $this->_first )->setCategory() ) {
				$_model
					->getEntered( $out['firstLevel'] )
					->getErrors( $out['arrError'] );
			}
			$_model->getLevel( $arrCats );
			foreach( $arrCats as $v ) {
				
				if ( !$_model->setPid( $v['id'] )->setData( $this->_second[$v['title']] )->setCategory() ) {
					$_model
						->getEntered( $out['subLevel'] )
						->getErrors( $out['arrError'] );
				}
			}
		}
	
	}
	
	public function initLang ($fr,$sp,$de) {

	$_model=new Core_Category( 'Clickbank' );
	$_model->setMode( 'edit' )->getTree( $this->out['arrTree'] );
	
	$this->_first = null;
	$this->_second = null;
	
	if ( $this->getFirstSecond($fr) ) {
		$_firstFr = $this->_first;
		$_secondFr = $this->_second;		
	}
	$this->_first = null;
	$this->_second = null;
	
	if ( $this->getFirstSecond($sp) ) {
		$_firstSp = $this->_first;
		$_secondSp = $this->_second;		
	}
	$this->_first = null;
	$this->_second = null;
	
	if ( $this->getFirstSecond($de) ) {
		$_firstDe = $this->_first;
		$_secondDe = $this->_second;		
	} 
	$this->_first = null;
	$this->_second = null;
	
	$_lngId=-1;$_lngIdFirst=-1;
	$_newTree = array ();	
	foreach ($this->out['arrTree'] as $k => $v) {
		$_lngIdFirst++;
		$_newTree[] = array (	'id' => $this->out['arrTree'][$k]['id'],
								'level' => $this->out['arrTree'][$k]['level'],
								'user_id' => $this->out['arrTree'][$k]['user_id'],
								'priority' => $this->out['arrTree'][$k]['priority'],
								'title' => $this->out['arrTree'][$k]['title'],
								'title_lng' => array ( 	1=>$this->out['arrTree'][$k]['title_lng'][1],
														2=>$_firstFr[$_lngIdFirst],
														3=>$_firstSp[$_lngIdFirst],
														4=>$_firstDe[$_lngIdFirst] )
								);
								$_titleFr = $_firstFr[$_lngIdFirst];
								$_titleSp = $_firstSp[$_lngIdFirst];
								$_titleDe = $_firstDe[$_lngIdFirst];
								
					$_lngIdSecond=-1;	
		foreach ($this->out['arrTree'][$k]['node'] as $l => $w) {
			$_lngIdSecond++;
			$_newTree[] = array (	'id' => $this->out['arrTree'][$k]['node'][$l]['id'],
									'level' => $this->out['arrTree'][$k]['node'][$l]['level'],
									'user_id' => $this->out['arrTree'][$k]['node'][$l]['user_id'],
									'priority' => $this->out['arrTree'][$k]['node'][$l]['priority'],
									'title' => $this->out['arrTree'][$k]['node'][$l]['title'],
									'pid' => $this->out['arrTree'][$k]['node'][$l]['pid'],
									'title_lng' => array ( 	1=>$this->out['arrTree'][$k]['node'][$l]['title_lng'][1],
															2=>$_secondFr[$_titleFr][$_lngIdSecond],
															3=>$_secondSp[$_titleSp][$_lngIdSecond],
															4=>$_secondDe[$_titleDe][$_lngIdSecond] )
									);		
		}
	}

	foreach ( $_newTree as $v ) {
	$_model->getLng()->set( $v );
	}
	
	}
	
}


?>
