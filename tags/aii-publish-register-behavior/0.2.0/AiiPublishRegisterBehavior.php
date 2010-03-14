<?php

/**
 * This behavior implements logic supporting publishing 
 * and then registering JS and CSS owner assets,
 * but may be used for publishing other filetypes as well.
 * Design of this behavior is focused on supporting widgets
 * with publishing own JS and CSS assets, which usually 
 * are located under widget subdirectory.
 * This subdirectory needs to be know to this behavior
 * and it can be passed to behavior using {@link basePath} property value.
 * Later, this subdirectory is avaliable also as {basePath} placeholder.
 * Usually you need to set it during behaviors initialization following
 * <code>
 *	$this->attachBehavior( 'pubRegManager',
 *		array(
 *			'class' => 'AiiPublishRegisterBehavior',
 *			'cssPath' => false,
 *			'jsToRegister' => array( 'audio-player.js' ),
 *			'basePath' => __FILE__,
 *			'toPublish' => array( 'mp3Folder' => $this->mp3Folder ),
 *	) );
 * </code>
 * If you need to publish assets from directory which is not owner subdirectory
 * you should can do thi with this behavior as well.
 * 
 * Note that you can use also other placeholders when specifying paths to publish
 * e.g. {assets} which points to {@link assetsPath}
 * {js} which points to {@link jsPath}
 * {css} which points to {@link cssPath}
 * 
 * By default behavior initializes all path properties with following value
 * referencing to some aforementioned placeholders
 *  - {@link assetsPath} is initialized with <code>{basePath}/assets</code>
 *  - {@link jsPath} is initialized with <code>{assets}/js</code>
 *  - {@link cssPath} is initialized with <code>{assets}/css</code>
 *  
 *  Hint: you can use {/} as alias for DIRECTORY_SEPARATOR
 *  
 *  Note that {@link otherResToPublish} are not set by default.
 *  You can use this e.g. to publish such resorces as audio files, archieves, etc.
 *  
 *  If you need to register published CSS and JS files 
 *  they need to be listed using properties 
 *  (please refer them to see how to specify this files):
 *  - {@link cssToRegister} 
 *  - {@link jsToRegister}
 *  
 * You can also register Yii core scripts it owner need them by setting
 * {@link coreScriptsToRegister}.
 * 
 * Note that by default all JS scripts are registered in head. You can change
 * this beaavior by setting {@link jsDefaultPos} to one of constant 
 * defined in {@link CClientScript}
 * 
 * All CSS files registered uses media type all. You can change this behavior by 
 * setting {@link defaultMedia} to other value, e.g. screen.
 * 
 * Note that {@link cssToRegister} and {@link jsToRegister} allows to specify
 * media type and position of registered JS script for each file separatelly.
 * 
 * If you need this behavior just for registering or paths passed to 
 * {@link assetsPath} {@link jsPath} {@link cssPath} are mixture of published
 * and unpublished path, you can inform this behavior about this by setting  
 * {@link publishingStatus} to proper value. Setted value will inform behavior which 
 * path points is published/unpublished.
 * For example, value 6 means that ot via {@link cssPath} and {@link jsPath} 
 * are passed published paths.
 * Set 1 {@link NOT_PUBLISHED} to inform that nothing is published yet
 * Add 2 {@link JS_PUBLISHED} to inform that {@link jsPath} is published
 * Add 4 {@link CSS_PUBLISHED} to inform that {@link cssPath} is published 
 * Add 8 {@link ASSETS_PUBLISHED} to inform that {@link assetsPath} is published
 * Add 16 {@link OTHERS_PUBLISHED} to inform that all resources from {@link otherResToPublish} are published
 * 
 * You can also update status by calling method {@link updateStatus} but we awared 
 * that this method do not check if particular resource is really published.
 * Note also that calling this method twice for the same resource 
 * will result with unproper {@link publishingStatus}  property value.
 * To be sure that you are not changing status for second time, use method {@link checkIsPublished}
 * 
 * 
 * @author Tomasz Suchanek <tomasz.suchanek@gmail.com>
 * @copyright Copyright &copy; 2010 Tomasz "Aztech" Suchanek
 * @link http://code.google.com/p/aii-extensions
 * @license http://www.yiiframework.com/license/
 * @package aii.extensions
 * @version 0.1.0
 **/
class AiiPublishRegisterBehavior extends CBehavior
{	
	
	/**
	 * @var message category
	 */
	const NOT_PUBLISHED 		= 1;
	const JS_PUBLISHED 			= 2;
	const CSS_PUBLISHED 		= 4;
	const ASSETS_PUBLISHED		= 8;
	const OTHERS_PUBLISHED 		= 16;
	
	/**
	 * @var string equals to initial value of {@link jsPath} 
	 * if {assets} reference were found in {@link jsPath} definition
	 */
	private $_jsPathTemplate;

	/**
	 * @var string equals to initial value of {@link cssPath} 
	 * if {assets} reference were used in {@link cssPath}
	 */
	private $_cssPathTemplate;
	
	/**
	 * Stores all files or directories which were published.
	 */
	private $_published = array( );
	
	/**
	 * 
	 * @var array stores ll generated paths
	 */
	private $_paths = array( );
	
	/**
	 * @var boolean determines if paths and placeholders are generated
	 */
	private $_pathsGenerated = false;
	
	/**
	 * @var integer  
	 * {@link assetsPath}, {@link cssPath} and {@link jsPath}
	 * are already published paths. This is mainly used when we know
	 * published paths and we want just to register CSS and JS files.
	 * If false, each path will be published
	 * This value don't affects files passed into {@link toPublish} property
	 */
	public $publishingStatus = self::NOT_PUBLISHED;	
	
	/**
	 * Array of pairs <id>-<file name>.
	 * Setting id is not mandatory. Please use them in case you will
	 * need to know where file or directory were published.
	 * You can do this by passing <id> into {@link getPublished}
	 * @var other resources that need to be published
	 */
	public $otherResToPublish = array( );	
	
	/**
	 * @var string base path directory, usually set to directory where owner of behaviour is found 
	 */
	public $basePath;	
	
	/**
	 * @var string folder path to assets
	 * You can use here {basePath} placeholder
	 * Set false if content shouldn't be published
	 * Default to null, meaning path '{basePath}{/}assets' will be used
	 * Note that {basePath} is detrmined basing on {@link basePath} value
	 */
	public $assetsPath = null;
	
	/**
	 * @var string folder path to css
	 * You can use here {assets} and {basePath} placeholders
	 * Set false if no css should be published
	 * Default to null, meaning path '{assets}{/}css' will be used
	 */
	public $cssPath = null;
	
	/**
	 * @var string folder path to js
	 * You can use here {assets} and {basePath} placeholders
	 * Set false if no JS should be published
	 * Default to null, meaning path '{assets}{/}js' will be used
	 */
	public $jsPath = null;
	
	/**
	 * @var array css files under {@link cssPath} to register
	 * Array may consist only <cssFile> or pairs <media>-<cssFile> 
	 * or its mixture, e.g
	 *	<code>
	 *		array( 
	 *			'print' => 'print.css',
	 *			'screen, projection' => 'main.css',
	 *			'ie.css',
	 *		);
	 *	</code>
	 * Leaving media empty, will set published css as media 'all'
	 */
	public $cssToRegister = array( );
	
	/**
	 * @var array js files under {@link jsPath} to register
	 * Array may consist only <jsFile> or pairs <jsFile>-<position>
	 * or its mixture, e.g.
	 *	<code>
	 *		array( 
	 *			'some.js' => '2',
	 *			'load.js' => CClientScript::POS_LOAD,
	 *			'head.js'
	 *		);
	 *	</code>
	 * If position is not specified, by default position from
	 * {@link jsDefaultPos} is used
	 */
	public $jsToRegister = array( );
	
	/**
	 * @var array core scripts to register
	 * Array consisting core names scipts to register, e.g.
	 * 	<code>
	 *		array ( 'jquery' , 'yiiactiveform' );
	 * 	</code>
	 */
	public $coreScriptsToRegister = array( );
	
	/**
	 * @var integer, the position of the JavaScript code.
	 * Not that this value differs from Yii default, first is 1 (meaning HEAD) later is 4.
	 */
	public $jsDefaultPos = CClientScript::POS_HEAD;
	
	/**
	 * 
	 * @var string default media type, defualt empty meaning all media types
	 */
	public $defaultMedia = '';
	
	/**
	 * @var boolean, set to true if assets should be shared among other extensions
	 */
	public $share = false;
	
	/**
	 * used internally to initialize bahavior
	 */
	private function initBehavior( )
	{
		$this->buildPaths( );
	}
	
	/**
	 * 
	 * @param string $key key taken from array {@link otherResToPublish} 
	 * Other avaliable keys are {basePath} {assets} {css} {js} 
	 * @return string to published resource or false if published file not found
	 */
	public function getPublished( $key )
	{
		return isset( $this->_published[$key] ) ? $this->_published[$key] : false;
	}
	
	/**
	 * Used internal, build paths, by replacing placeholders {@link assetsPath}, {@link cssPath} and {@link jsPath}
	 */
	protected function buildPaths( )
	{
		if ( $this->_pathsGenerated === false )
		{
			#set defualts value if needed
			if ( $this->assetsPath === null )
				$this->assetsPath = '{basePath}{/}assets';
			if ( $this->cssPath === null )
				$this->cssPath = '{assets}{/}css';
			if ( $this->jsPath === null )
				$this->jsPath = '{assets}{/}js';
			
			#create real paths when placeholders used			
			$this->_paths['assets'] = strtr( $this->assetsPath, $this->getTr( ) ); 
			$this->_cssPathTemplate = ( substr_count( $this->cssPath , '{assets}' ) > 0 ) ? $this->cssPath : null;
			$this->_paths['css'] = strtr( $this->cssPath , $this->getTr( ) );
			$this->_jsPathTemplate = ( substr_count( $this->jsPath , '{assets}' ) > 0 ) ? $this->jsPath : null;
			$this->_paths['js'] = strtr( $this->jsPath , $this->getTr( ) );
			$this->_pathsGenerated = true;
		}
	}
	
	/**
	 * publish Yii core scripts
	 */
	public function registerCoreScripts( )
	{
		if ( empty( $this->coreScriptsToRegister ) )
			Yii::trace( Yii::t( 'aii-publish-register-behavior' , 'No core scripts to register' ) );
		else		
		{
			foreach ( $this->coreScriptsToRegister as $coreScript )
			{
				Yii::app()->clientScript->registerCoreScript( $coreScript );
				Yii::trace( Yii::t( 'aii-publish-register-behavior' , 'Core script "{script}" registered.' , array( '{script}' => $coreScript ) ) );
			}
		}
	}
	
	/**
	 * Register JS scripts, CSS files and core scripts  
	 * If needed files are not published, implicit publishing is done
	 */
	public function registerAll( )
	{
		$this->registerCoreScripts( );		
		$this->registerCssFiles( );
		$this->registerJsFiles( );
	}
	
	/**
	 * All css files form {@link cssToRegister} are registered
	 * If {@link cssPath} is not published it is published here
	 * If {@link cssPath} is a subfolder of {@link assetsPath} the later is published
	 * @return boolean, false if there is nothing to register
	 */
	public function registerCssFiles( )
	{
		if ( !empty( $this->cssToRegister ) && $this->publishCssFiles( ) )
		{
				
			#register all CSS files
			foreach ( $this->cssToRegister  as $cssFile )
			{
				#css file name
				$cssFileName = is_string( $cssFile ) ? $cssFile : $cssFile['name'];
				#position where it should ne registered
				$media = isset( $cssFile['media'] ) ? $cssFile['media'] : $this->defaultMedia;
				#published resource 
				$cssPubFile = $this->_published['{css}'].DIRECTORY_SEPARATOR.$cssFileName;
				if ( !Yii::app()->clientScript->isCssFileRegistered( $cssPubFile , $media) )
				{
					Yii::app()->clientScript->registerScript( $jcssPubFile , $media );
					Yii::trace( 
						Yii::t( 'aii-publish-register-behavior' , 
							'Css file "{css}" was registered as {registered}.' , 
							array( '{css}' => $cssFileName , '{registered}' => $cssPubFile, 
					) ) );					
				}
			}
			return true;	
		}
		else
			return false;
	}
	
	/**
	 * All js files form {@link jsToRegister} are registered
	 * If {@link jsPath} is not published it is published here
	 * If {@link jsPath} is a subfolder of {@link assetsPath} the later is published
	 * @return boolean, false if nothing were registered
	 */	
	public function registerJsFiles( )
	{
		if ( !empty( $this->jsToRegister ) && $this->publishJsFiles( ) )
		{
			#register all JS files
			foreach ( $this->jsToRegister  as $jsFile => $pos )
			{
				#js file name
				$jsFileName = empty( $pos ) || is_integer( $jsFile ) ? $pos : $jsFile;
				#position where it should ne registered
				$jsPos = empty( $pos ) || is_integer( $jsFile ) ? $this->jsDefaultPos : $pos;
				#published resource 
				$jsPubFile = $this->_published['{js}'].'/'.$jsFileName;
				if ( !Yii::app()->clientScript->isScriptRegistered( $jsPubFile , $jsPos ) )
				{
					Yii::app()->getClientScript( )->registerScriptFile( $jsPubFile , $jsPos );
					Yii::trace( 
						Yii::t( 'aii-publish-register-behavior' , 
							'JS file "{js}" was registered as {registered}.' , 
							array( '{js}' => $jsFileName , '{registered}' => $jsPubFile, 
					) ) );					
				}
			}
			return true;
		}
		
		return false;
	}

	/**
	 * Publish all files in from directory specified in {@link assetsPath}
	 * Published resource is available via placeholder {assets}
	 * @return string published path
	 */
	public function publishAssets( )
	{	
		if ( !$this->checkIsPublished( self::ASSETS_PUBLISHED ) )
		{
			$this->updateStatus( self::ASSETS_PUBLISHED );
			return $this->_published['{assets}'] = CHtml::asset( strtr( $this->assetsPath , $this->getTr( ) ), $this->share );
		}
	}
	
	/**
	 * Publish CSS fiels path. In case CSS file path is in reference to {@link assetsPath}
	 * this methiod publish them if they are not published yet.
	 * Note that assets reference is recognized by {assets} placeholder
	 * @return string or false; published CSS files path or false if nothing to publish
	 */
	public function publishCssFiles( )
	{
		$this->initBehavior( );
		#if css files path was not set, do nothing
		if ( $this->cssPath === false )
			return false;
			
		if ( !$this->checkIsPublished( self::CSS_PUBLISHED ) )
		{
			#if cssPath has {assets} placeholder publish all assets
			if ( $this->cssPathTemplate )
			{
				$this->publishAssets( );
				$this->published['{css}'] = strtr(  $this->_cssPathTemplate , $this->getTr( ) );
			}
			#publish "traditional" way
			else
				$this->_published['{css}'] = CHtml::asset( $this->cssPath , $this->share );
		}
		#check if user sets already published css files path
		elseif ( !isset ( $this->_published['{css}'] ) )
			$this->_published['{css}'] = $this->cssPath;
		
		$this->updateStatus( self::CSS_PUBLISHED );
		return $this->_published['{css}'];
	}
	
	/**
	 * Publish JS files path. In case JS file path is in reference to {@link assetsPath}
	 * this methiod publish them if they are not published yet. 
	 * Note that assets reference is recognized by {assets} placeholder
	 * @return string or false; published JS files path or false if nothing to publish
	 */
	public function publishJsFiles( )
	{
		$this->initBehavior( );
		#if js files path was not set, do nothing		
		if ( $this->jsPath === false )
			return false;
			
		if ( !$this->checkIsPublished( self::JS_PUBLISHED ) )
		{
			#if jsPath has {assets} placeholder publish all assets			
			if ( $this->_jsPathTemplate )
			{				
				$this->publishAssets( );
				$this->_published['{js}'] = strtr( $this->_jsPathTemplate , $this->getTr( ) );
			}
			#publish "traditional" way
			else
				$this->_published['{js}'] = CHtml::asset( $this->jsPath , $this->share );
		}
		#check if user sets already published js files path		
		elseif ( !isset ( $this->_published['{js}'] ) )
			$this->_published['{js}'] = $this->jsPath;

		$this->updateStatus( self::JS_PUBLISHED );
		return $this->_published['{js}'];
	}
	
	/**
	 * Publish resources from {@link otherResToPublish}
	 * @return boolean false if nothing was published
	 */
	public function publishResources( )
	{		
		$this->initBehavior( );
		#start only if resources was not published 
		if ( !$this->checkIsPublished( self::OTHERS_PUBLISHED ) )
		{
			if ( empty ( $this->otherResToPublish ) )
			{
				Yii::trace( Yii::t( 'aii-publish-register-behavior', 'There are no resources to publish.' ) );
				return false;
			}
			else
			{
				foreach ( $this->otherResToPublish as $key => $res )
				{
					$this->_published[$key] = CHtml::asset( strtr( $res , $this->getTr( ) ) , $this->share );
					Yii::trace( Yii::t( 'aii-publish-register-behavior' , 'Resource "{res}" published as {published}.' , array( '{res}' => $res , '{published}' => $this->getPublished( $key ) ) ) );				
				}
				$this->updateStatus( self::OTHERS_PUBLISHED );
			}
		}
		return true; 
	}
	
	/**
	 * This method publish files from all specified path (assets, css, js, resources)
	 */
	public function publishAll( )
	{
		$this->publishResources( );
		$this->publishAssets( );
		$this->publishCssFiles( );		
		$this->publishJsFiles( );
	}
	
	/**
	 * Check if resource is published 
	 * @param integer $level resource level number 
	 * @return boolean,	true if particular resource is published
	 */
	public function checkIsPublished( $level = null )
	{
		if ( $level === null )
			return ( $this->publishingStatus === ( self::INITIAL + self::JS_PUBLISHED + self::CSS_PUBLISHED + self::ASSETS_PUBLISHED + self::OTHERS_PUBLISHED ) );			
		elseif ( ( $level % 2 ) !== 0 )
			throw new CException( Yii::t( 'aii-publish-register-behavior' , 'Level need to be power of 2!' ) );
		return floor( $this->publishingStatus / $level ) % 2 === 1;
	}
	
	/**
	 * updates publishing status
	 * @param integer $level resource level number
	 */
	public function updateStatus( $level )
	{
		$this->publishingStatus += $level;
	}

	/**
	 * @return array array used in strtr function to replace placeholders with real values
	 */
	private function getTr( )
	{
		$tr = array( );
		$tr['{/}'] = DIRECTORY_SEPARATOR;
		$tr['{basePath}'] = $this->basePath;
		
		if ( $this->checkIsPublished( self::JS_PUBLISHED ) )
			$tr['{js}'] =  $this->_published['{js}'];
		if ( $this->checkIsPublished ( self::CSS_PUBLISHED ) )
			$tr['{css}'] = $this->_published['{css}'];
		if ( $this->checkIsPublished ( self::ASSETS_PUBLISHED ) )
			$tr['{assets}'] = $this->_published['{assets}'];		
 		return $tr;		
	}
}
?>