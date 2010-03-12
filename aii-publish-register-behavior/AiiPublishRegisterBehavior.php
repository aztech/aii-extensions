<?php

/**
 * This behavior implements logic supporting publishing 
 * and then registering JS and CSS owners assets,
 * but may be used for publishing other filetypes as well.
 * Design of this behavior is focused on supporting widgets,
 * which usually consist assets to publish in own subdirectory. 
 * This directory should be filled if you want to use {basePath} placeholder
 * which points to {@link basePath} property value
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
 * If you need to publish assets from directory which is not owners subdirectory
 * you should can do thi with this behavior as well.
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
	const MSG_CAT = 'aii-publish-register-behavior';
	const NOT_PUBLISHED 		= 1;
	const JS_PUBLISHED 			= 2;
	const CSS_PUBLISHED 		= 4;
	const ASSETS_PUBLISHED		= 8;
	const OTHERS_PUBLISHED 		= 16;
	
	/**
	 * @var CClientScript client script
	 */
	private $_cs;
	
	/**
	 * @var CAssetManager asset manager
	 */
	private $_am;
	
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
	 * Array may consist only <jsFile> or pairs <position>-<jsFile> 
	 * or its mixture, e.g.
	 *	<code>
	 *		array( 
	 *			'2' => 'some.js',
	 *			CClientScript::POS_LOAD => 'load.js',
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
	
	private function initBehavior( )
	{
		if ( !isset( $this->_cs ) )
			$this->_cs = Yii::app()->clientScript;
		if ( !isset( $this->_am ) )
			$this->_am = Yii::app()->getAssetManager();
		$this->buildPaths( );
	}
	
	/**
	 * 
	 * @param string $key key taken from array {@link toPublish} 
	 * Other avaliable keys are {basePath} {assets} {css} {js} 
	 * @return string or false if published file not found
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
	 * @param boolean, if it should initialize behavior to support aliases like {assets}
	 * and publish assets
	 * used internally for registering core scripts
	 */
	public function registerCoreScripts( $initialized = false )
	{
		if ( empty( $this->coreScriptsToRegister ) )
			Yii::trace( Yii::t( self::MSG_CAT , 'No core scripts to register' ) );
		else		
		{
			foreach ( $this->coreScriptsToRegister as $coreScript )
			{
				$this->_cs->registerCoreScript( $coreScript );
				Yii::trace( Yii::t( self::MSG_CAT , 'Core script "{script}" registered.' , array( '{script}' => $coreScript ) ) );
			}
		}
	}
	
	/**
	 * Register JS and CSS files.  
	 * If needed files are not published, implicit publishing is done
	 */
	public function registerAll( )
	{
		$this->registerCssFiles( );
		$this->registerCoreScripts( );
		$this->registerJsFiles( );
	}
	
	/**
	 * All css files form {@link cssToRegister} are registered
	 * If {@link cssPath} is not published, files are not registered
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
				if ( !$this->_cs->isCssFileRegistered( $cssPubFile , $media) )
				{
					$this->_cs->registerScript( $jcssPubFile , $media );
					Yii::trace( 
						Yii::t( self::MSG_CAT , 
							'Css file  "{css}" was registered as {regisered}.' , 
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
	 * All css files form {@link cssToRegister} are registered
	 * If {@link cssPath} is not published it is published here
	 * If {@link cssPath} is a subfolder of {@link assetsPath} later is published
	 * If {@link pathsPublished} it means that {@link cssPath} is already published
	 * @return boolean, false if nothing were registered
	 */	
	public function registerJsFiles( )
	{
		if ( !empty( $this->jsToRegister ) && $this->publishJsFiles( ) )
		{
				
			#register all JS files
			foreach ( $this->jsToRegister  as $jsFile )
			{
				#js file name
				$jsFileName = is_string( $jsFile ) ? $jsFile : $jsFile['name'];
				#position where it should ne registered
				$jsPos = isset( $jsFile['pos'] ) ? $jsFile['pos'] : $this->jsDefaultPos;
				#published resource 
				$jsPubFile = $this->_published['{js}'].DIRECTORY_SEPARATOR.$jsFileName;
				if ( !$this->_cs->isScriptRegistered( $jsPubFile , $jsPos ) )
				{
					$this->_cs->registerScript( $jsPubFile , $jsPos );
					Yii::trace( 
						Yii::t( self::MSG_CAT , 
							'JS file  "{js}" was registered as {regisered}.' , 
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
			return $this->_published['{assets}'] = CHtml::asset( strtr( $this->assetsPath , $this->getTr( ) ), $this->share );
	}
	
	/**
	 * Publish CSS fiels path. In case CSS file path is in reference to {@link assetsPath}
	 * publish whole assets path. Note that assets reference is recognized by {assets} placeholder
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
	 * Publish JS files path. In case KS file path is in reference to {@link assetsPath}
	 * publish whole assets path. Note that assets reference is recognized by {assets} placeholder
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
	 * Publish resources
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
				Yii::trace( Yii::t( self::MSG_CAT, 'There are no resources to publish.' ) );
				return false;
			}
			else
			{
				foreach ( $this->otherResToPublish as $key => $res )
				{
					$this->_published[$key] = CHtml::asset( strtr( $res , $this->getTr( ) ) , $this->share );
					Yii::trace( Yii::t( self::MSG_CAT , 'Resource "{res}" published as {published}.' , array( '{res}' => $res , '{published}' => $this->getPublished( $key ) ) ) );				
				}
				$this->updateStatus( self::OTHERS_PUBLISHED );
			}
		}
		return true; 
	}
	
	/**
	 * This methid publish all needed assets for specified CSS and JS files.
	 */
	public function publishAll( )
	{
		$this->publishResources( );
		$this->publishAssets( );
		$this->publishCssFiles( );		
		$this->publishJsFiles( );
	}
	
	/**
	 * 
	 * @param integer $level resource level number 
	 * @return boolean,	true if particular resource is published
	 */
	private function checkIsPublished( $level = null )
	{
		if ( $level === null )
			return ( $this->publishingStatus === ( self::INITIAL + self::JS_PUBLISHED + self::CSS_PUBLISHED + self::ASSETS_PUBLISHED + self::OTHERS_PUBLISHED ) );			
		elseif ( ( $level % 2 ) !== 0 )
			throw new CException( Yii::t( self::MSG_CAT , 'Level need to be power of 2!' ) );
		return floor( $this->publishingStatus / $level ) % 2 === 1;
	}
	
	/**
	 * updates publishing status
	 * @param integer $level resource level number
	 */
	private function updateStatus( $level )
	{
		if ( $this->checkIsPublished ( $level ) )
			$this->publishingStatus += $level;
	}

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