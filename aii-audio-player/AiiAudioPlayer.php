<?php
	/**
	 * This Widget is using Audio Player Wordpress plugin from 1 pixel out
	 * {@link http://www.1pixelout.net/code/audio-player-wordpress-plugin/}
	 * This widget concerns using aforementioned player for non-Wordpress projects
	 * 
	 * To see more information about using aforementioned player for non-Wordpress project, 
	 * please see {@link http://www.macloo.com/examples/audio_player/}
	 * 
	 * To see more inormation about options of Audio Player Wordpress plugin
	 * read tutorial "Customizing Audio Player" 
	 * {@link http://www.macloo.com/examples/audio_player/options.html}
	 * 
	 * This extension requires {@link AiiJsAndCssPublishRegisterBehavior}
	 * for publishing assets
	 * 
	 * @author Tomasz Suchanek <tomasz.suchanek@gmail.com>
	 * @copyright Copyright &copy; 2008-2010 Tomasz "Aztech" Suchanek
	 * @license http://www.yiiframework.com/license/
	 * @package aii.extensions
	 * @version 0.1.0
	 * @uses {@link AiiJsAndCssPublishRegisterBehavior}
	 */
  class AiiAudioPlayer extends CWidget
  {
  	
  	/**
  	 * @var string - Player Id (needed when using multiple players on one site)
	 * default to 'audioplayer'
  	 */
  	public $playerID = 'audioplayer';
  	
  	/**
  	 * 
  	 * @var string - hex value - Background color string e.g. 
  	 */
  	public $bg;
  	
  	/**
  	 * 
  	 * @var string - hex value - Left background
  	 */
  	public $leftbg;
  	/**
  	 * 
  	 * @var string - hex value - Left icon 
  	 */
  	public $lefticon;
  	
  	/**
  	 * 
  	 * @var string - hex value - The color the right background will change to on mouseover
  	 */
  	public $rightbg;
  	
  	/**
  	 * 
  	 * @var string - hex value -The color the right background will change to on mouseover
  	 */
  	public $rightbghover;
  	
  	/**
  	 * 
  	 * @var string - hex value - Right icon
  	 */
  	public $righticon;
  	
    /**
     * 
     * @var string - hex value - The color the right icon will change to on mouseover
     */  	
  	public $righticonhover;

  	/**
  	 * 
  	 * @var string - hex value - The color of text 
  	 */
  	public $text;
  	
  	/**
  	 * 
  	 * @var string - hex value - The color of slider
  	 */
  	public $slider;
  	
  	/**
  	 * 
  	 * @var string - hex value - unknown_type
  	 */
  	public $track;
  	
  	/**
  	 * 
  	 * @var string - hex value - This is the line surrounding the loader bar
  	 */
  	public $border;
  	
  	
    /**
     * 
     * @var string - hex vlaue - This is color of loader
     */
  	public $loader;

  	/**
  	 * 
  	 * @var boolean - Should mp3 looping all the time?
	 * Default to false
  	 */
  	public $loop = false;
  	
  	/**
  	 * 
  	 * @var boolean - Should mp3 start just after loading?
	 * Default to false
  	 */
  	public $autostart = false;

  	/**
  	 * 
  	 * @var string - mp3 file name (including extension) from folder {@link mp3Folder}
  	 */
    public $mp3;  	
	
  	/**
  	 * 
  	 * @var integer - object height, default to 24
	 * Please change this value, when using own CSS, 
	 * where players height differs from default
  	 */	
	public $height = 24;
	
  	/**
  	 * 
  	 * @var integer - object width, default to 480
	 * Please change this value, when using own CSS, 
	 * where players width differs from default
  	 */		
	public $width = 480;
  	
    /**
     * 
     * @var string - JS filename
     */
  	private $playerJSFile = 'audio-player.js';
  	
  	/**
  	 * 
  	 * @var string - SWF player filename
  	 */
  	private $playerSWFFile = 'player.swf';
  	
  	/**
  	 * 
  	 * @var string - Publised folder with mp3 files
	 * Default to null, which means that standard '{basepath}/mp3' folder under
	 * extension directory will be published
  	 */
  	protected $mp3Folder = null;
  	
  	
  	/**
  	 * If param value is not set param name points also to class variable.
  	 * @param $name
  	 * @param $value - optional
  	 * @return string options
  	 */
  	private function buildOption( $name , $amp = true , $value = null )
  	{
  		$optionValue = ( $value !== null ) ? $value : $this->{$name};
  		$ampStr = ( $amp === true ) ? '&' : '';
  		return empty( $optionValue ) ? '' : "{$ampStr}{$name}={$optionValue}";
  	}
  	
  	/**
  	 * (non-PHPdoc)
  	 * @see web/widgets/CWidget#init()
  	 */
    public function init()
    {
    	parent::init( );
    	if ( $this->mp3Folder === null )
    		$this->mp3Folder = '{basePath}{/}mp3';
    	$this->attachBehavior( 'pubMan', #publishManager
    		array(
    			'class' => 'AiiPublishRegisterBehavior',
    			'cssPath' => false,
    			'jsToRegister' => array( 'audio-playe.js' ),
    			'ownerDirectory' => __FILE__,
    			'toPublish' => array( 'mp3Folder' => $this->mp3Folder ),
    	) );
    	$this->publishAndRegister( );
    }
  	
  	/**
  	 * (non-PHPdoc)
  	 * @see web/widgets/CWidget#run()
  	 */
  	public function run()
  	{
  		#first prepare flash variables basing on options
  		# - required flash variables
  		$flashVars = $this->buildOption( 'playerID' , false );
		
  		# - optional flash variables
  		$flashVars .= $this->buildOption( 'bg' );
  		$flashVars .= $this->buildOption( 'leftbg' );
  		$flashVars .= $this->buildOption( 'lefticon' );
  		$flashVars .= $this->buildOption( 'rightbg' );
  		$flashVars .= $this->buildOption( 'rightbghover' );
		$flashVars .= $this->buildOption( 'righticon' );
  		$flashVars .= $this->buildOption( 'righticonhover' );
  		$flashVars .= $this->buildOption( 'text' );
  		$flashVars .= $this->buildOption( 'slider' );
  		$flashVars .= $this->buildOption( 'track' );
  		$flashVars .= $this->buildOption( 'border' );
  		$flashVars .= $this->buildOption( 'loader' );
  		$flashVars .= $this->buildOption( 'loop' , $this->loop ? 'yes' : 'no' );
  		$flashVars .= $this->buildOption( 'autostart' , $this->autostart ? 'yes' : 'no' );
  		#mp3 file name
		$flashVars .= $this->buildOption( 'soundFile' , true , $this->getPublished( 'mp3Folder' ).'/'.$this->mp3 ); 
  		#render
		$this->renderContent( $flashVars );
  	}
	
	private function renderParam( $name , $value )
	{
		#note that param tags are not closed if embeded in object	
		return CHtml::openTag( 'param' , array( 'name' => $name, 'value' => $value ) );
	}
	
	protected function rendercontent( $flashVars )
	{
		if ( empty( $this->height ) )
			throw new CException( Yii::t( 'aii-audio-player' , 'Height can\'t be empty' ) );
			
		if ( empty( $this->width ) )
			throw new CException( Yii::t( 'aii-audio-player' , 'Width can\'t be empty' ) );
			
		echo CHtml::openTag( 'object' , array( 
			'id' => $this->playerID,		
			'type' => 'application/x-shockwave-flash',
			'data' => $this->getPublished( '{assets}' ).'/'.$this->playerSWFFile,
			'height' => $this->height,
			'width' => $this->width
		) );
		echo $this->renderParam( 'movie' , $this->getPublished( '{assets}' ).'/'.$this->playerSWFFile );
		echo $this->renderParam( 'FlashVars' , $flashVars );
		echo $this->renderParam( 'quality' , 'high' );
		echo $this->renderParam( 'menu' , 'false' );
		echo $this->renderParam( 'wmode' , 'transparent' );
		echo CHtml::closeTag( 'object' );
	}
  }
?>