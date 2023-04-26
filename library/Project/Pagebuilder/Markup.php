<?php 

define('BASE_MARKUP_PATH', Zend_registry::get('config')->path->absolute->pagebuilder . 'elements' . DIRECTORY_SEPARATOR . 'skeleton.html');

class Project_Pagebuilder_Markup {
    private $modal_markup = '<div class="modal fade" tabindex="-1" role="dialog"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div class="modal-body"></div></div></div></div>';
    private $domXpath = null;
    
    private $document = null;
    private $defaultDom = array();

    private $addCss = array();
    private $addJs = array();
    private $addScript = null;
    private $customUserJsCss = array();

    private $styles = array();
    private $scripts = array();
    private $cssFiles = array();
    private $jsFiles = array();

    private $imageFiles = array();
    private $fontFiles = array();
    private $config = array();


    public function __construct ( $config = array() ) {
    
        $this->document = new DOMDocument;
        $this->config = $config;
        
        $this->init();
    }

    /** Inital function */
    private function init() {
        /** Getting base markup */
        if( $this->document->loadHTMLFile( BASE_MARKUP_PATH ) ) {
            $this->defaultDom['container'] = $this->document->getElementById( 'page' );
            $this->defaultDom['popups'] = $this->document->getElementById( 'popups' );
            $this->defaultDom['head'] = $this->document->getElementsByTagName( 'head' )->item(0);
            $this->defaultDom['body'] = $this->document->getElementsByTagName( 'body' )->item(0);

            $this->domXpath = new DOMXpath( $this->document );
            $this->replaceBasePath();
            $this->addBaseTag( $this->config['url'] );
        }
    }

    /** Setter AddCss */
    public function AddCss( $css ) {
        if( ! empty( $css ) ) {
            $this->addCss = $css;
        }

        return $this;
    }

    /** Setter AddJs */
    public function AddJs( $js ) {
        if( ! empty( $js ) ) {
            $this->addJs = $js;
        }

        return $this;
    }

    /** Setter AddScript */
    public function AddScript( $js ) {
        if( ! empty( $js ) ) {
            $this->addScript = $js;
        }

        return $this;
    }

    /** Setter customUserJsCss */
    public function CustomUserCssJs( $custom ) {
        if( ! empty( $custom ) ) {
            $this->customUserJsCss = $custom;
        }

        return $this;
    }

    /** Replace base path for <link>, <script> tags */
    private function replaceBasePath() {
        foreach( $this->domXpath->query( '//*[@src]' ) as $node ) {
            $node->parentNode->removeChild( $node );
            $this->jsFiles[] = Zend_Registry::get( 'config' )->path->html->pagebuilder . 'elements/' . $node->getAttribute( 'src' );
        }

        foreach( $this->domXpath->query( '//*[@href]' ) as $node ) {
            $node->parentNode->removeChild( $node );
            $this->cssFiles[] = Zend_Registry::get( 'config' )->path->html->pagebuilder . 'elements/' . $node->getAttribute( 'href' );
        }
    }

    /** Added <base /> tag in markup */
    private function addBaseTag( $url ) {
        $baseNode = $this->document->createElement('base');
        $baseNode->setAttribute('href', sprintf('//%s%s', parse_url( $url, PHP_URL_HOST ), parse_url( $url, PHP_URL_PATH ) ) );

        $this->defaultDom['head']->insertBefore( $baseNode, $this->defaultDom['head']->childNodes->item(0) );
    }

    /** Generate block markup */
    public function addPartial( $html ) {
        $partialDom = new DOMDocument;

        if( $partialDom->loadHTML( $html ) ){
            $this->parsingEffectBlocks( $partialDom );
            $this->parsingCodeBlock( $partialDom );

            $domXpath = new DOMXpath( $partialDom );

            /** Remove all blocks with class name overly */
            foreach( $domXpath->query( '//div[@class="overly"]' ) as $element ) {
                $element->parentNode->removeChild( $element );
            }

            /** Added all markup in a default DOM */
            foreach( $partialDom->getElementById( 'page' )->childNodes as $node ) {
                $this->defaultDom['container']->appendChild( $this->document->importNode( $node, true ) );
            }

            /** Added all CSS files in a default DOM */
            foreach( $partialDom->getElementsByTagName( 'link' ) as $link ) {
                if( ! in_array( $link->getAttribute( 'href' ), $this->cssFiles ) ) {
                    $this->cssFiles[] = $link->getAttribute( 'href' );
                }
            }

            /** Added all CSS files in a default DOM */
            foreach( $partialDom->getElementsByTagName( 'style' ) as $style ) {
                // $this->defaultDom['head']->appendChild( $this->document->importNode( $style, true ) );
                if( ! in_array( $style->childNodes->item(0)->nodeValue, $this->styles ) ) {
                    $this->styles[] = trim( $style->childNodes->item(0)->nodeValue );
                }
            }

            /** Added all JS code & files in a default DOM */
            foreach( $partialDom->getElementsByTagName( 'script' ) as $script ) {
                if( ! empty( $script->getAttribute( 'src' ) ) ) {
                    if( ! in_array( $script->getAttribute( 'src' ), $this->jsFiles ) ) {
                        // $this->defaultDom['body']->appendChild( $this->document->importNode( $script ) );
                        $this->jsFiles[] = $script->getAttribute( 'src' );
                    }
                } else {
                    if( ! in_array( $script->childNodes->item(0)->nodeValue, $this->scripts ) ) {
                        $this->scripts[] = $script->childNodes->item(0)->nodeValue;
                    }

                    // $this->defaultDom['body']->appendChild( $this->document->importNode( $script, true ) );
                }
            }
        }
    }

    /** Generate Modal markup */
    public function addModal( $html, $params = array() ) {
        $modalContainer = new DOMDocument;
        $modalContainer->loadHTML( $this->modal_markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

        $containerXpath = new DOMXpath( $modalContainer );
        $modalContainerNode = $containerXpath->query('//div[@class="modal-body"]')->item(0);
        
        $modalHtml = new DOMDocument;
        if( $modalHtml->loadHTML( $html ) ){
            $this->parsingEffectBlocks( $modalHtml );

            $htmlXpath = new DOMXpath( $modalHtml );
            foreach( $htmlXpath->query('//div[@class="modal-body"]')->item(0)->childNodes as $node ){
                $modalContainerNode->appendChild( $modalContainer->importNode( $node, true ) );
            }

            /** Added all CSS files in a default DOM */
            foreach( $modalHtml->getElementsByTagName( 'link' ) as $link ) {
                if( ! in_array( $link->getAttribute( 'href' ), $this->cssFiles ) ) {
                    $this->cssFiles[] = $link->getAttribute( 'href' );
                }
            }

            /** Added all JS code & files in a default DOM */
            foreach( $modalHtml->getElementsByTagName( 'script' ) as $script ) {
                if( ! empty( $script->getAttribute( 'src' ) ) ) {
                    if( ! in_array( $script->getAttribute( 'src' ), $this->jsFiles ) ) {
                        // $this->defaultDom['body']->appendChild( $this->document->importNode( $script ) );
                        $this->jsFiles[] = $script->getAttribute( 'src' );
                    }
                } else {
                    if( ! in_array( $script->childNodes->item(0)->nodeValue, $this->scripts ) ) {
                        $this->scripts[] = $script->childNodes->item(0)->nodeValue;
                    }
                }
            }
        }


        $modalNode = $modalContainer->childNodes->item(0);
        
        if ( $params['popup'] === 'entry' ) {
            $modalNode->setAttribute( 'data-popup', 'entry' );
        } else if ( $params['popup'] === 'exit' ) {
            $modalNode->setAttribute( 'data-popup', 'exit' );
        }

        if ( ! empty( $params['popup_settings'] ) ) {
            $settings = json_decode( $params['popup_settings'], true );

            if ( isset( $settings['popupReoccurrence'] ) ){
                if ( $settings['popupReoccurrence'] == 'Once' ) $modalNode->setAttribute( 'data-popup-occurrence' , 'once' );
                else  if ( $settings['popupReoccurrence'] == 'All' ) $modalNode->setAttribute( 'data-popup-occurrence' , 'all' );
            }

            if ( isset( $settings['popupDelay'] ) ){
                $modalNode->setAttribute( 'data-popup-delay', $settings['popupDelay'] );
            }

            if ( $params['popup'] === 'regular' && isset( $settings['popupID'] ) ){
                $modalNode->setAttribute( 'id', $settings['popupID'] );
            } 
        }

        $modalNode->setAttribute( 'data-popup-id', $params['id'] );
        $this->defaultDom['popups']->appendChild( $this->document->importNode( $modalNode, true ) );
    }

    /** Add <title> in to DOM */
    public function setTitlePage( $title ) {
        $titleNode = $this->document->createElement( 'title', $title );
        $this->defaultDom['head']->insertBefore( $titleNode, $this->defaultDom['head']->childNodes->item(0) );
    }

    /** Add <meta name="description/keywords"> in to DOM  */
    public function setMeta( $meta ) {
        $metaDescription = $this->document->createElement( 'meta' );
        $metaDescription->setAttribute( 'name', 'description' );

        if( isset( $meta['description'] ) ) {
            $metaDescription->setAttribute( 'content', $meta['description'] );
        }

        $metaKeywords = $this->document->createElement( 'meta' );
        $metaKeywords->setAttribute( 'name', 'keywords' );

        if( isset( $meta['keywords'] ) ) {
            $metaKeywords->setAttribute( 'content', $meta['keywords'] );
        }

        $this->defaultDom['head']->appendChild( $metaDescription );
        $this->defaultDom['head']->appendChild( $metaKeywords );
    }

    /** Find all images in DOM */
    private function findAllImages() {
        /** Extract all <image> elements */
        foreach( $this->document->getElementsByTagName( 'img' ) as $image ) {
            /** Check file path is has remote url */
            if( strpos( $image->getAttribute( 'src' ), 'http' ) === false ) {
                $this->imageFiles[] = $image->getAttribute('src');
                $image->setAttribute( 'data-src', 'bundles/' . pathinfo( $image->getAttribute( 'src' ), PATHINFO_BASENAME ) );
            } else {
                $image->setAttribute( 'data-src', $image->getAttribute( 'src' ) );
            }

            $image->setAttribute( 'src', 'data:image/gif;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=' );
            $image->setAttribute( 'class', $image->getAttribute( 'class' ) . ' lazyload' );
        }

        /** Extract parallax data-image-src files */
        foreach ($this->domXpath->query('//*[@data-parallax]') as $element){
            $element->setAttribute( 'data-image-src', 'bundles/' . pathinfo( $element->getAttribute( 'data-image-src' ), PATHINFO_BASENAME ) );
            $this->imageFiles[] = 'bundles/' . pathinfo( $element->getAttribute( 'data-image-src' ), PATHINFO_BASENAME );
        }

        /** Extract images in the style attribute */
        foreach ($this->domXpath->query('//*[@style]') as $style){
            $re = '/url\(\s*[\'"]?(\S*\.(?:jpe?g|gif|png))[\'"]?\s*\)[^;}]*?/i';
            if( preg_match_all( $re, $style->getAttribute( 'style' ), $matches ) ){

                /** Check file path is has remote url */
                foreach ($matches[1] as $imgPath){
                    if( strpos( $imgPath, '//' ) === false ) {
                        $this->imageFiles[] = $imgPath;
                        $style->setAttribute( 'style', str_replace( $imgPath, 'bundles/' . pathinfo( $imgPath, PATHINFO_BASENAME ), $style->getAttribute( 'style' ) ) );
                    }
                }
            }
        }

        /** Extract images from a css files */
        foreach ($this->cssFiles as $cssLink){
            /** extract CSS link, no need for blob uhrls */
            if( substr($cssLink, 0, 4) != 'blob' ){

                /** extract images from CSS */
                if( substr( $cssLink, 0, 4 ) != 'http' && substr( $cssLink, 0, 2 ) != '//' ){
                    $CSS = file_get_contents( Zend_Registry::get( 'config' )->path->absolute->root . $cssLink );

                    $re = '/url\(\s*[\'"]?(\S*\.(?:jpe?g|gif|png))[\'"]?\s*\)[^;}]*?/i';
                    if( preg_match_all( $re, $CSS, $matches ) ){
                        foreach( $matches[1] as $imgPath ){
                            $this->imageFiles[] = Zend_Registry::get('config')->path->html->pagebuilder . 'elements/' . str_replace( '../', '', $imgPath );
                        }
                    }
                }
            }
        }

        return $this;
    }

    /** Find all fonts in css files */
    private function findFonts() {
        /** Extract images from a css files */
        foreach ($this->cssFiles as $cssLink){
            /** extract CSS link, no need for blob uhrls */
            if( substr($cssLink, 0, 4) != 'blob' ){

                /** extract images from CSS */
                if( substr( $cssLink, 0, 4 ) != 'http' && substr( $cssLink, 0, 2 ) != '//' ){
                    $CSS = file_get_contents( Zend_Registry::get( 'config' )->path->absolute->root . $cssLink );

                    /** extract fonts from CSS */
                    $re = '/(?<=url\()[\'"]?(?!=http|https)(.*?\.(woff2|eot|woff|ttf|svg)).*?[\'"]?(?=\))/i';
                    if( preg_match_all( $re, $CSS, $matches ) ){
                        foreach( $matches[1] as $font ){
                            $info = new SplFileInfo( Zend_Registry::get( 'config' )->path->absolute->root . pathinfo( $cssLink, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR . $font );
                            if( $info->getRealPath() != false ){
                                $this->fontFiles[] = Zend_Registry::get('config')->path->html->pagebuilder . 'elements/' . str_replace( '../', '', $font );
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    /** Add all css and js code to generated page */
    private function addJsCssToPage() {
        if( ! empty( $this->styles ) ){
            $inlineStyles = join( '', $this->styles );
            $inlineStyles = str_replace( '../', '', $inlineStyles );
            $styleNode = $this->document->createElement( 'style', $inlineStyles );
            $this->defaultDom['head']->appendChild( $styleNode );
        }

        if( ! empty( $this->addCss ) ) {
            $this->cssFiles = array_unique( array_merge( $this->cssFiles, $this->addCss ) );
        }

        if( ! empty( $this->cssFiles ) ) {
            foreach( $this->cssFiles as $cssLink ) {
                $linkNode = $this->document->createElement( 'link' );
                $linkNode->setAttribute( 'rel', 'stylesheet' );
                $linkNode->setAttribute( 'href', $cssLink );

                if( strpos( $cssLink, 'http' ) === false ) {
                    $linkNode->setAttribute( 'rel', 'stylesheet' );
                    $linkNode->setAttribute( 'href', 'bundles/' . pathinfo( $cssLink, PATHINFO_BASENAME ) );
                } else {
                    $linkNode->setAttribute( 'rel', 'stylesheet' );
                    $linkNode->setAttribute( 'href', $cssLink );
                }

                $this->defaultDom['head']->appendChild( $linkNode );
            }
        }

        if( ! empty( $this->addJs ) ) {
            $this->jsFiles = array_unique( array_merge( $this->jsFiles, $this->addJs ) );
        }

        if( ! empty( $this->addScript ) ) {
            $this->scripts[] = $this->addScript;
        }

        if( ! empty( $this->scripts ) ) {
            $scriptNode = $this->document->createElement( 'script', join( '', $this->scripts ) );
            $this->defaultDom['head']->appendChild( $scriptNode );
        }

        if( ! empty( $this->jsFiles ) ) {
            foreach( $this->jsFiles as $jsLink ) {
                $scriptNode = $this->document->createElement( 'script' );
                $scriptNode->setAttribute( 'type', 'text/javascript' );
                $scriptNode->setAttribute( 'src', 'bundles/' . pathinfo( $jsLink, PATHINFO_BASENAME ) );
                $scriptNode->setAttribute( 'defer', true );
    
                $this->defaultDom['body']->appendChild( $scriptNode );
            }
        }

        return $this;
    }

    /** Add animate effect for blocks */
    private function parsingEffectBlocks( $domDocument ) {
        $domXpath = new DOMXpath( $domDocument );
        
        /** Find all elements with have a attr data-effects */
        foreach( $domXpath->query('//*[@data-effects]') as $element ) {
            $effectType = $element->getAttribute( 'data-effects' );

            if( $effectType !== 'none' ){
                $effectDelay = (int)$element->getAttribute( 'data-delayef' );
                $effectId = $element->getAttribute( 'data-id' );

                $classList = $element->getAttribute( 'class' );

                if( strpos( $classList, 'hide' ) === false ) {
                    $element->setAttribute( 'class', $classList . ' hide' );
                }
            }
        }
    }

    /** Parsing component HTML/JS CODE */
    private function parsingCodeBlock( $domDocument ) {
        $domXpath = new DOMXpath( $domDocument );
        $chunk = new DOMDocument;

        $codeElements = $domXpath->query( '//*[@class="code"]' );
        while( $codeElements->length > 0) {
            $element = $codeElements->item( 0 );
            $option = $element->getAttribute('data-option');

            /** Decode string */
            $option = base64_decode( $option );
            $container = $domDocument->createElement( 'div' );

            if( $option !== false && $chunk->loadHTML( $option, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
                
                /** Moving elements in new a container element */
                foreach( $chunk->childNodes as $node ) {
                    $container->appendChild( $domDocument->importNode( $node, true ) );
                }
            }

            /** Replace element with a class .codeblock on parsed HTML */
            $element->parentNode->parentNode->replaceChild( $container, $element->parentNode );
            $codeElements = $domXpath->query( '//*[@class="code"]' );
        }
    }

    /** Add custom CSS/JS user code */
    public function addCustomUserCssJs() {
        $dom = new DOMDocument;

        /** Add [pages_header_includes] to a <head> */
        if( ! empty( $this->customUserJsCss['pages_header_includes'] ) && $dom->loadHTML( $this->customUserJsCss['pages_header_includes'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
            $includes = $dom->childNodes;
            foreach( $includes as $node ) {
                $this->defaultDom['head']->appendChild( $this->document->importNode( $node, true ) );
            }
        }

        /** Add [pages_css] to a <head> */
        if( ! empty( $this->customUserJsCss['pages_css'] ) ) {
            $style = $this->document->createElement( 'style', $this->customUserJsCss['pages_css'] );
            $this->defaultDom['head']->appendChild( $style );
        }

        /** Add [global_css] to a <head> */
        if( ! empty( $this->customUserJsCss['global_css'] ) ) {
            $style = $this->document->createElement( 'style', $this->customUserJsCss['global_css'] );
            $this->defaultDom['head']->appendChild( $style );
        }

        /** Add [header_script] to a <head> */
        if( ! empty( $this->customUserJsCss['header_script'] ) && $dom->loadHTML( $this->customUserJsCss['header_script'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
            $includes = $dom->childNodes;

            foreach( $includes as $node ) {
                $this->defaultDom['head']->appendChild( $this->document->importNode( $node, true ) );
            }
        }

        /** Add [footer_script] to a <body> */
        if( ! empty( $this->customUserJsCss['footer_script'] ) && $dom->loadHTML( $this->customUserJsCss['footer_script'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
            $includes = $dom->childNodes;

            foreach( $includes as $node ) {
                $this->defaultDom['body']->appendChild( $this->document->importNode( $node, true ) );
            }
        }

        /** Add [pages_header_script] to a <head> */
        if( ! empty( $this->customUserJsCss['pages_header_script'] ) && $dom->loadHTML( $this->customUserJsCss['pages_header_script'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
            $includes = $dom->childNodes;

            foreach( $includes as $node ) {
                $this->defaultDom['head']->appendChild( $this->document->importNode( $node, true ) );
            }
        }

        /** Add [pages_footer_script] to a <body> */
        if( ! empty( $this->customUserJsCss['pages_footer_script'] ) && $dom->loadHTML( $this->customUserJsCss['pages_footer_script'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
            $includes = $dom->childNodes;

            foreach( $includes as $node ) {
                $this->defaultDom['body']->appendChild( $this->document->importNode( $node, true ) );
            }
        }
    }

    /** Return HTML code of generated page */
    public function returnHTML( &$buffer, &$files ) {

        $this
            ->findAllImages()
            ->findFonts()
            ->addJsCssToPage()
            ->addCustomUserCssJs();

        $files['css'] = array_unique( $this->cssFiles );
        $files['js'] = array_unique( $this->jsFiles );
        $files['font'] = array_unique( $this->fontFiles );
        $files['image'] = array_unique( $this->imageFiles );

        $buffer = $this->document->saveHTML();

        $this->reset();
    }

    /** Reset all field to base state */
    private function reset() {
        $this->addCss = array();
        $this->addJs = array();
        $this->addScript = null;
        $this->customUserJsCss = array();
    
        $this->styles = array();
        $this->scripts = array();
        $this->cssFiles = array();
        $this->jsFiles = array();
    
        $this->imageFiles = array();
        $this->fontFiles = array();

        $this->init();
    }
}