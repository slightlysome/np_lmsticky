<?php
/*
    LMSticky Nucleus plugin
    Copyright (C) 2013 Leo (www.slightlysome.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
	(http://www.gnu.org/licenses/gpl-2.0.html)
	
	See lmsticky/help.html for plugin description, install, usage and change history.
*/
class NP_LMSticky extends NucleusPlugin
{
	var $pageParm;
	
	// name of plugin 
	function getName()
	{
		return 'LMSticky';
	}

	// author of plugin
	function getAuthor()
	{
		return 'Leo (www.slightlysome.net)';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL()
	{
		return 'http://www.slightlysome.net/nucleus-plugins/np_lmsticky';
	}

	// version of the plugin
	function getVersion()
	{
		return '1.0.1';
	}

	// a description to be shown on the installed plugins listing
	function getDescription()
	{
		return 'Let you stick one or more items on the index page.';
	}

	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			case 'SqlApi':
				return 1;
			case 'HelpPage':
				return 1;
			default:
				return 0;
		}
	}
	
	function hasAdminArea()
	{
		return 1;
	}
	
	function getMinNucleusVersion()
	{
		return '360';
	}
	
	function getTableList()
	{	
		return 	array();
	}
	
	function getEventList() 
	{ 
		return array('AdminPrePageFoot', 'PostParseURL', 
				'LMReplacementVars_BlogExtraQuery', 'LMReplacementVars_ArchiveExtraQuery', 'LMReplacementVars_ArchListExtraQuery', 
				'LMReplacementVars_PrevNextExtraQuery'
				
				); 
	}
	
	function getPluginDep() 
	{
		return array('NP_LMReplacementVars');
	}

	function install()
	{
		$sourcedataversion = $this->getDataVersion();

		$this->upgradeDataPerform(1, $sourcedataversion);
		$this->setCurrentDataVersion($sourcedataversion);
		$this->upgradeDataCommit(1, $sourcedataversion);
		$this->setCommitDataVersion($sourcedataversion);					
	}
	
	function unInstall()
	{
		global $manager;
		
		if ($this->getOption('del_uninstall') == 'yes')	
		{
			foreach ($this->getTableList() as $table) 
			{
				sql_query("DROP TABLE IF EXISTS ".$table);
			}
		}
	}

	function event_AdminPrePageFoot(&$data)
	{
		// Workaround for missing event: AdminPluginNotification
		$data['notifications'] = array();
			
		$this->event_AdminPluginNotification($data);
			
		foreach($data['notifications'] as $aNotification)
		{
			echo '<h2>Notification from plugin: '.htmlspecialchars($aNotification['plugin'], ENT_QUOTES, _CHARSET).'</h2>';
			echo $aNotification['text'];
		}
	}
	
	////////////////////////////////////////////////////////////
	//  Events
	function event_AdminPluginNotification(&$data)
	{
		global $member, $manager;
		
		$actions = array('overview', 'pluginlist', 'plugin_LMSticky');
		$text = "";
		
		if(in_array($data['action'], $actions))
		{			
			if(!$this->_checkReplacementVarsSourceVersion())
			{
				$text .= '<p><b>The installed version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin needs version '.$this->_needReplacementVarsSourceVersion().' or later of the LMReplacementvars plugin to function properly.</b> The latest version of the LMReplacementvars plugin can be downloaded from the LMReplacementvars <a href="http://www.slightlysome.net/nucleus-plugins/np_lmreplacementvars">plugin page</a>.</p>';
			}

			if($manager->pluginInstalled('NP_LMBlogPaginate'))
			{
				if(!$this->_checkBlogPaginateSourceVersion())
				{
					$text .= '<p><b>The installed version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin needs version '.$this->_needBlogPaginateSourceVersion().' or later of the LMBlogPaginate plugin to function properly.</b> The latest version of the LMBlogPaginate plugin can be downloaded from the LMBlogPaginate <a href="http://www.slightlysome.net/nucleus-plugins/np_lmblogpaginate">plugin page</a>.</p>';
				}
			}
			
			if($manager->pluginInstalled('NP_LMFancierURL'))
			{
				if(!$this->_checkFancierURLSourceVersion())
				{
					$text .= '<p><b>The installed version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin needs version '.$this->_needFancierURLSourceVersion().' or later of the LMFancierURL plugin to function properly.</b> The latest version of the LMFancierURL plugin can be downloaded from the LMFancierURL <a href="http://www.slightlysome.net/nucleus-plugins/np_lmfancierurl">plugin page</a>.</p>';
				}
			}
			
			$sourcedataversion = $this->getDataVersion();
			$commitdataversion = $this->getCommitDataVersion();
			$currentdataversion = $this->getCurrentDataVersion();
		
			if($currentdataversion > $sourcedataversion)
			{
				$text .= '<p>An old version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin files are installed. Downgrade of the plugin data is not supported. The correct version of the plugin files must be installed for the plugin to work properly.</p>';
			}
			
			if($currentdataversion < $sourcedataversion)
			{
				$text .= '<p>The version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin data is for an older version of the plugin than the version installed. ';
				$text .= 'The plugin data needs to be upgraded or the source files needs to be replaced with the source files for the old version before the plugin can be used. ';

				if($member->isAdmin())
				{
					$text .= 'Plugin data upgrade can be done on the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' <a href="'.$this->getAdminURL().'">admin page</a>.';
				}
				
				$text .= '</p>';
			}
			
			if($commitdataversion < $currentdataversion && $member->isAdmin())
			{
				$text .= '<p>The version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin data is upgraded, but the upgrade needs to commited or rolled back to finish the upgrade process. ';
				$text .= 'Plugin data upgrade commit and rollback can be done on the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' <a href="'.$this->getAdminURL().'">admin page</a>.</p>';
			}
		}
		
		if($text)
		{
			array_push(
				$data['notifications'],
				array(
					'plugin' => $this->getName(),
					'text' => $text
				)
			);
		}
	}

	function event_PostParseURL(&$data)
	{
		global $manager, $CONF;
		
		if($manager->pluginInstalled('NP_LMFancierURL') && $CONF['URLMode'] == 'pathinfo')
		{
			// Get params from LMFancierURL
			if(method_exists($this->_getFancierURLPlugin(), 'getURLValue'))
			{
				$aPageParm = $this->_getFancierURLPlugin()->getURLValue('page');
				
				if($aPageParm)
				{
					$this->pageParm = array_shift($aPageParm);
				}
				else 
				{
					$this->pageParm = 0;
				}
			}
		}
		else
		{
			// Get params the normal way
			$this->pageParm = intRequestVar('page');
		}
	}

	function event_LMReplacementVars_BlogExtraQuery(&$data)
	{
		$this->_handleSticky($data);
	}
	
	function event_LMReplacementVars_ArchiveExtraQuery(&$data)
	{
		$this->_handleSticky($data);
	}

	function event_LMReplacementVars_ArchListExtraQuery(&$data)
	{
		$this->_handleSticky($data);
	}

	function event_LMReplacementVars_PrevNextExtraQuery(&$data)
	{
		$this->_handleSticky($data);
	}

	function _handleSticky(&$data)
	{
		$blogid = $data['blog']->blogid;
		$extraquery = '';

		$lmsticky = 'disable';
		if(isset($data['skinvarparm']))
		{
			$skinvarparm = $data['skinvarparm'];
		
			if(isset($skinvarparm['lmsticky']))
			{
				$lmsticky = $skinvarparm['lmsticky'];
			}
		}

		$stickyitems = $this->getBlogOption($blogid, 'blogstickyitems');
		$onlyfirstpage = $this->getBlogOption($blogid, 'blogonlyfirstpage');
		$exclude = $this->getBlogOption($blogid, 'blogexclude');

		if($stickyitems)
		{
			$aSticky = explode(",", $stickyitems);
	
			foreach($aSticky as $itemid)
			{
				$itemid = intval($itemid);
					
				if($itemid)
				{
					if($lmsticky == 'enable')
					{
						if($onlyfirstpage == 'no' || $this->pageParm <= 1)
						{
							if($extraquery)
							{
								$extraquery .= ' OR ';
							}

							$extraquery .= 'i.inumber='.$itemid;
						}
					}
					else
					{
						if($exclude == 'yes')
						{
							if($extraquery)
							{
								$extraquery .= ' AND ';
							}

							$extraquery .= 'i.inumber<>'.$itemid;
						}
					}
				}
			}
			
			if($extraquery && $lmsticky == 'enable')
			{
				$extraquery = '('.$extraquery.')';
			}
			
			if(!$extraquery && $lmsticky == 'enable')
			{
				$extraquery = '1=2';
			}
		}
		else
		{
			if($lmsticky == 'enable')
			{
				$extraquery = '1=2';
			}
		}
		
		if($extraquery)
		{
			$data['extraquery']['lmsticky'] = $extraquery;
		}
	}
	
	////////////////////////////////////////////////////////////
	//  Private functions
	function &_getBlogPaginatePlugin()
	{
		global $manager;
		
		$oBlogPaginatePlugin =& $manager->getPlugin('NP_LMBlogPaginate');

		if(!$oBlogPaginatePlugin)
		{
			// Panic
			echo '<p>Couldn\'t get plugin NP_LMBlogPaginate. This plugin must be installed for the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin to work.</p>';
			return false;
		}
		
		return $oBlogPaginatePlugin;
	}
	
	function &_getFancierURLPlugin()
	{
		global $manager;
		
		$oFancierURLPlugin =& $manager->getPlugin('NP_LMFancierURL');

		if(!$oFancierURLPlugin)
		{
			// Panic
			echo '<p>Couldn\'t get plugin LMFancierURL. This plugin must be installed for the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin to work.</p>';
			return false;
		}
		
		return $oFancierURLPlugin;
	}

	function &_getReplacementVarsPlugin()
	{
		global $manager;
		
		$oReplacementVarsPlugin =& $manager->getPlugin('NP_LMReplacementVars');

		if(!$oReplacementVarsPlugin)
		{
			// Panic
			echo '<p>Couldn\'t get plugin LMReplacementVars. This plugin must be installed for the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin to work.</p>';
			return false;
		}
		
		return $oReplacementVarsPlugin;
	}

	/////////////////////////////////////////////////////
	// Data access and manipulation functions
	

	////////////////////////////////////////////////////////////////////////
	// Plugin Upgrade handling functions
	function getCurrentDataVersion()
	{
		$currentdataversion = $this->getOption('currentdataversion');
		
		if(!$currentdataversion)
		{
			$currentdataversion = 0;
		}
		
		return $currentdataversion;
	}

	function setCurrentDataVersion($currentdataversion)
	{
		$res = $this->setOption('currentdataversion', $currentdataversion);
		$this->clearOptionValueCache(); // Workaround for bug in Nucleus Core
		
		return $res;
	}

	function getCommitDataVersion()
	{
		$commitdataversion = $this->getOption('commitdataversion');
		
		if(!$commitdataversion)
		{
			$commitdataversion = 0;
		}

		return $commitdataversion;
	}

	function setCommitDataVersion($commitdataversion)
	{	
		$res = $this->setOption('commitdataversion', $commitdataversion);
		$this->clearOptionValueCache(); // Workaround for bug in Nucleus Core
		
		return $res;
	}

	function getDataVersion()
	{
		return 1;
	}
	
	function upgradeDataTest($fromdataversion, $todataversion)
	{
		// returns true if rollback will be possible after upgrade
		$res = true;
				
		return $res;
	}
	
	function upgradeDataPerform($fromdataversion, $todataversion)
	{
		// Returns true if upgrade was successfull
		
		for($ver = $fromdataversion; $ver <= $todataversion; $ver++)
		{
			switch($ver)
			{
				case 1:
					$this->createOption('currentdataversion', 'currentdataversion', 'text','0', 'access=hidden');
					$this->createOption('commitdataversion', 'commitdataversion', 'text','0', 'access=hidden');

					$this->createBlogOption('blogstickyitems','ItemIDs to stick on index page', 'text', '', '');
					$this->createBlogOption('blogonlyfirstpage', 'Only stick items on first index page (requires LMBlogPaginate plugin)', 'yesno','no');
					$this->createBlogOption('blogexclude', 'Exclude sticky items from normal index and archive', 'yesno','yes');

					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}
		
		return true;
	}
	
	function upgradeDataRollback($fromdataversion, $todataversion)
	{
		// Returns true if rollback was successfull
		for($ver = $fromdataversion; $ver >= $todataversion; $ver--)
		{
			switch($ver)
			{
				case 1:
					$res = true;
					break;
				
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}

		return true;
	}

	function upgradeDataCommit($fromdataversion, $todataversion)
	{
		// Returns true if commit was successfull
		for($ver = $fromdataversion; $ver <= $todataversion; $ver++)
		{
			switch($ver)
			{
				case 1:
					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}
		return true;
	}
	
	function _needBlogPaginateSourceVersion()
	{
		return '1.0.0';
	}
	
	function _checkBlogPaginateSourceVersion()
	{
		$blogPaginateVersion = $this->_needBlogPaginateSourceVersion();
		$aVersion = explode('.', $blogPaginateVersion);
		$needmajor = $aVersion['0']; $needminor = $aVersion['1']; $needpatch = $aVersion['2'];
		
		$blogPaginateVersion = $this->_getblogPaginatePlugin()->getVersion();
		$aVersion = explode('.', $blogPaginateVersion);
		$major = $aVersion['0']; $minor = $aVersion['1']; $patch = $aVersion['2'];
		
		if($major < $needmajor || (($major == $needmajor) && ($minor < $needminor)) || (($major == $needmajor) && ($minor == $needminor) && ($patch < $needpatch)))
		{
			return false;
		}

		return true;
	}

	function _needFancierURLSourceVersion()
	{
		return '3.0.0';
	}
	
	function _checkFancierURLSourceVersion()
	{
		$fancierURLVersion = $this->_needFancierURLSourceVersion();
		$aVersion = explode('.', $fancierURLVersion);
		$needmajor = $aVersion['0']; $needminor = $aVersion['1']; $needpatch = $aVersion['2'];
		
		$fancierURLVersion = $this->_getFancierURLPlugin()->getVersion();
		$aVersion = explode('.', $fancierURLVersion);
		$major = $aVersion['0']; $minor = $aVersion['1']; $patch = $aVersion['2'];
		
		if($major < $needmajor || (($major == $needmajor) && ($minor < $needminor)) || (($major == $needmajor) && ($minor == $needminor) && ($patch < $needpatch)))
		{
			return false;
		}

		return true;
	}

	function _needReplacementVarsSourceVersion()
	{
		return '1.0.0';
	}
	
	function _checkReplacementVarsSourceVersion()
	{
		$replacementVarsVersion = $this->_needReplacementVarsSourceVersion();
		$aVersion = explode('.', $replacementVarsVersion);
		$needmajor = $aVersion['0']; $needminor = $aVersion['1']; $needpatch = $aVersion['2'];
		
		$replacementVarsVersion = $this->_getReplacementVarsPlugin()->getVersion();
		$aVersion = explode('.', $replacementVarsVersion);
		$major = $aVersion['0']; $minor = $aVersion['1']; $patch = $aVersion['2'];
		
		if($major < $needmajor || (($major == $needmajor) && ($minor < $needminor)) || (($major == $needmajor) && ($minor == $needminor) && ($patch < $needpatch)))
		{
			return false;
		}

		return true;
	}
}
?>
