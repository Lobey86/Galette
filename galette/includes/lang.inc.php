<?
	include(WEB_ROOT."lang/lang_".PREF_LANG.".php");
	function _T($chaine)
	{
		// echo "$chaine";die();
		if (!isset($GLOBALS["lang"][$chaine]))
//			return $chaine." (not translated)";
			return $chaine;
		elseif ($GLOBALS["lang"][$chaine]=="")
			return $chaine." (not translated)";
		else
			return $GLOBALS["lang"][$chaine];
	}
?>
