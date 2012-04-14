<?php
/**
 *  @Copyright   Copyright (C) 2012 by Nicolas OGIER www.nicolas-ogier.fr
 *  @package     TIMELINE - op2k_timeline - Timeline for Joomla 2.5
 *  @author      Nicolas OGIER {@link http://www.nicolas-ogier.fr}
 *  @version     2.5-1.0 - 06-April-2012
 *  @link        http://www.nicolas-ogier.fr/download/timeline-joomla.html
 *
 *  @license GNU/GPL
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
// protection de la page
defined('_JEXEC') or die('Restricted access');
//----------------------
// On se connecte au framework Joomla.
jimport('joomla.plugin.plugin');
//----------------------
class plgContentTimeline extends JPlugin
{
	/**
	 * Constructeur du plugin...
	 * @param unknown_type $subject
	 * @param unknown_type $config
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
	}
	/**
	 * Appel du plugin lors de la preparation du contenu
	 * @param $context	string	The context of the content being passed to the plugin.
	 * @param $article	object	The content object.  Note $article->text is also available
	 * @param $params	object	The content params
	 * @param $page		int		The 'page' number
	 * @since	1.6
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		$tag="timeline";
		$regex = '#{'.$tag.'(.*?)}(.*?){/'.$tag.'}#s';
		
			$article->text = preg_replace_callback($regex, array(&$this, '_timelineReplace'), $article->text);

		return true;
	}
	/**
	 * Remplacement des tags par la Timeline
	 *
	 * @param	array	An array of matches (see preg_match_all)
	 * @return	string
	 */
	private function _timelineReplace (&$matches) {
		jimport('joomla.utilities.utility');
		$document = JFactory::getDocument();
		// récupération des éléments en paramètres
		$argument = $this->_cleancode($matches[2]);
		// Choix du mode d'insertion des données
		$text = '<div id="timeline"></div>';
		if(substr_count($argument,'https://docs.google.com/spreadsheet/')){
			// on a utilisé un fichier google
			$insertion = '"'.$argument.'"';
		} else {
			if(substr_count($argument,'.json')){
			$insertion = '"'.JURI::base() . $argument.'"';
			}
			 else {
			 	// on a passé des données en paramètres
			 	$insertion="";
			 	$element_timeline = $this->_timelineElements($argument);
			 	$text = '<div id="timeline">'.$element_timeline.'</div>';
			 }
		}
		$lang =& JFactory::getLanguage();
		$code_lang = $lang->getTag();
		
		$document->addStyleSheet(JURI::base(true).'/plugins/content/timeline/plugin_timeline/timeline.css');
		$document->addScript(JURI::base(true) . '/plugins/content/timeline/plugin_timeline/jquery-min.js');
		$document->addScript(JURI::base(true) . '/plugins/content/timeline/plugin_timeline/timeline-min.'.$code_lang.'.js');
		$this->_timelineCreateJS($code_lang);
		$javascript='<script type="text/javascript">
			$(document).ready(function() {
				var timeline = new VMM.Timeline();	
				timeline.init('.$insertion.');	
			});			
		</script>';
		$document->addCustomTag($javascript);
		$css = "<style>
		#timeline{width:100%;height:700px; }
		</style>";
		$document->addCustomTag($css);
		return $text;

	}
	
	/**
	 * Nettoye les entitées HTML
	 * @param string $text
	 */
	private function _cleancode (&$text){
		$html_entities_match = array("|\<br \/\>|", "#<#", "#>#", "|&#39;|", '#&quot;#', '#&nbsp;#');
		$html_entities_replace = array("\n", '&lt;', '&gt;', "'", '"', ' ');
		$text = preg_replace($html_entities_match, $html_entities_replace, $text);
		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace("\t", '  ', $text);
		return $text;
	}
	/**
	 * Récupère dans le texte de l'article la timeline
	 * @param unknown_type $text
	 */
	private function _timelineElements (&$text) {
		$html_formate ="";
		$text = str_replace('[', '<', $text);
		$text = str_replace(']', '>', $text);
		$html_code_match = array("<main>", "<title>", "</title>","<time>", "</time>", '<start>', '</start>','<text>','</text>','<figure>','</figure>','<img>','</img>','<cite>','</cite>','<caption>','</caption>','</main>','<events>','</events>','<end>','</end>','<name>','</name>','<link>','</link>','<event>','</event>');
		$html_code_replace = array("<section>", '<h2>', '</h2>','<time>', "</time>", '<time>', '</time>','<article>','</article>','<figure>','</figure>','<img src="','" >','<cite>','</cite>','<figcaption>','</figcaption>','</section>','<ul>','</ul>','<time>','</time>','<h3>','</h3>','<a href="','" >&nbsp;</a>','<li>','</li>');
		$html_formate = str_replace($html_code_match, $html_code_replace, $text).' <br />'.$text;
		return $html_formate;
	}
	/**
	 * Création du fichier Javascript de la timeline avec la traduction des dates
	 * @param string $codelangue
	 */
	private function _timelineCreateJS($codelangue) {
		$fichierJS=dirname(__FILE__).'/plugin_timeline/timeline-min.js';
		$fichier_original=file_get_contents($fichierJS);
		$fichierJSmodifie = dirname(__FILE__).'/plugin_timeline/timeline-min.'.$codelangue.'.js';
		$search='["January","February","March","April","May","June","July","August","September","October","November","December"]';
		$replace='["'.JText::_('JANUARY').'","'.JText::_('FEBRUARY').'","'.JText::_('MARCH').'","'.JText::_('APRIL').'","'.JText::_('MAY').'","'.JText::_('JUNE').'","'.JText::_('JULY').'","'.JText::_('AUGUST').'","'.JText::_('SEPTEMBER').'","'.JText::_('OCTOBER').'","'.JText::_('NOVEMBER').'","'.JText::_('DECEMBER').'"]';
		$fichier_modifie = str_replace($search, $replace, $fichier_original);
		$search ='["Jan.","Feb.","March","April","May","June","July","Aug.","Sept.","Oct.","Nov.","Dec."]';
		$replace='["'.JText::_('JANUARY_SHORT').'","'.JText::_('FEBRUARY_SHORT').'","'.JText::_('MARCH_SHORT').'","'.JText::_('APRIL_SHORT').'","'.JText::_('MAY_SHORT').'","'.JText::_('JUNE_SHORT').'","'.JText::_('JULY_SHORT').'","'.JText::_('AUGUST_SHORT').'","'.JText::_('SEPTEMBER_SHORT').'","'.JText::_('OCTOBER_SHORT').'","'.JText::_('NOVEMBER_SHORT').'","'.JText::_('DECEMBER_SHORT').'"]';
		$fichier_modifie = str_replace($search, $replace, $fichier_modifie);
		$search ='["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"]';
		$replace='["'.JText::_('SUNDAY').'","'.JText::_('MONDAY').'","'.JText::_('TUESDAY').'","'.JText::_('WEDNESDAY').'","'.JText::_('THURSDAY').'","'.JText::_('FRIDAY').'","'.JText::_('SATURDAY').'"]';
		$fichier_modifie = str_replace($search, $replace, $fichier_modifie);
		$search ='["Sun.","Mon.","Tues.","Wed.","Thurs.","Fri.","Sat."]';
		$replace ='["'.JText::_('SUN').'","'.JText::_('MON').'","'.JText::_('TUE').'","'.JText::_('WED').'","'.JText::_('THU').'","'.JText::_('FRI').'","'.JText::_('SAT').'"]';		$fichier_modifie = str_replace($search, $replace, $fichier_modifie);
		$fichier_modifie = str_replace($search, $replace, $fichier_modifie);
	
		$this->_createFile($fichierJSmodifie, $fichier_modifie);
		
	}
	
	/**
	 * Vérifie s'il n'existe pas et créer le fichier demandé
	 *
	 * @param string $path
	 * @param string $text
	 * @return unknown
	 */
	private function _createFile ( $path, &$text, $force = false )
	{
		if ( $force ) {
			$this->_writeFile($path, $text);
			return( true );
		} else {
			if ( !file_exists( $path ) ) {
				$this->_writeFile($path, $text);
				return( true );
			} else {
				return( false );
			}
		}
	}
		/**
		 * Ecrit dans le fichier $path le texte passé dans $text
		 * @param unknown_type $path
		 * @param unknown_type $text
		 */
	private  function _writeFile ($path, &$text) {
			$inFile = fopen( $path, "w" );
			fwrite( $inFile, $text );
			fclose( $inFile );
			unset( $inFile );
			return( true );
		}
}

