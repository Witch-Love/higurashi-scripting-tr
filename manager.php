<?php

define('LF', "\n");
define('CRLF', "\r\n");

// exclude empty files
$exclude = [
	'zonik_001_vm00_n01.txt',
	'zonik_001_vm0x_n01.txt',
	'zonik_002_vm00_n01.txt',
	'zonik_002_vm0x_n01.txt',
	'zonik_009_02_vm00_n01.txt',
	'zonik_009_02_vm0x_n01.txt',
	'zonik_012_vm00_n01.txt',
	'zonik_012_vm0x_n01.txt',
	'zonik_tips_01_vm00_n01.txt',
	'zonik_tips_01_vm0x_n01.txt',
	'zonik_tips_02_vm00_n01.txt',
	'zonik_tips_02_vm0x_n01.txt',
	'zonik_tips_17_vm00_n01.txt',
	'zonik_tips_17_vm0x_n01.txt',
	'zwata_010_02_vm00_n01.txt',
	'zwata_010_02_vm0x_n01.txt',
	'zwata_012_02_vm00_n01.txt',
	'zwata_012_02_vm0x_n01.txt',
	'zwata_012_vm00_n01.txt',
	'zwata_012_vm0x_n01.txt',
	'zwata_tips_01_vm00_n01.txt',
	'zwata_tips_01_vm0x_n01.txt',
	'zwata_tips_22_vm00_n01.txt',
	'zwata_tips_22_vm0x_n01.txt',
	'_zmeak_008_vm00_n01.txt',
	'_zmeak_008_vm0x_n01.txt',
	'_zmeak_012_vm00_n01.txt',
	'_zmeak_012_vm0x_n01.txt',
	'_zmeak_016_2_vm00_n01.txt',
	'_zmeak_016_2_vm0x_n01.txt',
	'flow.txt',
	'init.txt',
	'zwata_00x_vm00_n01.txt',
	'&choicesdialog.txt',
	'&endroll_sion.txt',
	'&endroll_staff5.txt',
	'&eye.txt',
	'&eyecamp.txt',
	'&opening.txt',
	'&toketu.txt',
	'&toketu2.txt',
	'&endroll_staff6.txt',
	'_mina_009_1.txt',
	'&eyecampblack.txt',
	'&endroll_staff7.txt',
	'&endroll_staff8.txt',
	'&endroll_staff9.txt',
	'&eyefragment.txt',
	'kakera_miss.txt',
	'&endroll.txt',
	'util.txt'
];

function err($m) {
	echo $m.PHP_EOL;
	die(0);
}

function readDirs($path) {
	$list = [];
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
	foreach ($iterator as $file) {
		if ($file -> isFile()) {
			if (!in_array(basename($file), $GLOBALS['exclude']) && !str_contains(basename($file), "vm0x") && !str_contains(basename($file), "vm00")) {
				array_push($list, str_replace('\\', '/', $file));
			}
		}
	}
	return $list;
}

function readDirsAll($path) {
	$list = [];
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
	foreach ($iterator as $file) {
		if ($file -> isFile()) {
			array_push($list, str_replace('\\', '/', $file));
		}
	}
	return $list;
}

function main($argc, $argv) {
	if ($argc < 2) err('');

	$scripts_dir = 'scripts';
	$extracted_dir = 'story';
	$out_dir = 'output-scripts';
	$char_json_dir = 'character_names.json';
	
	ini_set('memory_limit','2048M');

	switch ($argv[1]) {
		case 'extract':
			$list = [];
			$story = readDirs($scripts_dir);

			for ($i = 0; $i < count($story); $i++) {
				$chapter = $story[$i];
				
				$exp = '/^(?:\s{0,})NULL, ?"(.+)", ?.+\);/';

				$handle = fopen($chapter, "r");
				$newfile = [];
				if ($handle) {
					while (($line = fgets($handle)) !== false) {
						$result = preg_match($exp, $line, $matches, PREG_OFFSET_CAPTURE);
						if ($result == 1) {
							$match = $matches[1][0];
							$match = str_replace('\\"', '"', $match);
							array_push($newfile, '`' . $match . '`' . LF);
						} else {
							$z_exp = '/(?:GetGlobalFlag\(GCensor\) <= .\)\{ModCallScriptSection\(")(.*?)(?:",")(.*?)(?:")/';
							$result = preg_match($z_exp, $line, $matches, PREG_OFFSET_CAPTURE);
							if ($result == 1) {
								$parts = pathinfo($chapter);
								$in_handle = fopen($parts['dirname']."/".$matches[1][0].".txt", "r");
								if ($in_handle) {
									$state = false;
									$dialog_name = $matches[2][0];
									while (($in_line = fgets($in_handle)) !== false) {
										if (!$state) {
											if (str_contains($in_line, $dialog_name)) {
												$state = true;
											}
											continue;
										}

										$dialog_exp = '/dialog.*?\(/';
										$result = preg_match($dialog_exp, $in_line, $matches, PREG_OFFSET_CAPTURE);
										if ($result == 1) break;

										$result = preg_match($exp, $in_line, $matches, PREG_OFFSET_CAPTURE);
										if ($result == 1) {
											$match = $matches[1][0];
											$match = str_replace('\\"', '"', $match);
											array_push($newfile, '`' . $match . '`' . LF);
										}
									}
									fclose($in_handle);
								}
							}
						}
					}
					fclose($handle);
				}
				$new_path = str_replace($scripts_dir, $extracted_dir, $chapter);
				$dir = pathinfo($new_path)['dirname'];
				if (!file_exists($dir)) {
					mkdir($dir, 0777, true);
				}
				file_put_contents($new_path, $newfile);
			}
			break;
		case 'insert':
			$extracted_files = readDirs($extracted_dir);
			$datas = [];
			for ($i = 0; $i < count($extracted_files); $i++) {
				$data = [];
				$file = $extracted_files[$i];

				$handle = fopen($file, "r");
				$n = 0;
				if ($handle) {
					while (($line = fgets($handle)) !== false) {
						$data[$n] = str_replace('`', '', $line);
						$n++;
					}
					fclose($handle);
				}
				$name = basename($file);
				$datas[$name] = $data;
			}




			recurseCopy("$scripts_dir/", "$out_dir/");
			$story = readDirs($out_dir);

			for ($i = 0; $i < count($story); $i++) {
				$chapter = $story[$i];

				$exp = '/^((?:\s{0,})NULL, ?")(.+)(", ?.+\);)/';

				$handle = fopen($chapter, "r");
				$newfile = [];
				$n = 0;
				if ($handle) {
					while (($line = fgets($handle)) !== false) {
						$result = preg_match($exp, $line, $matches, PREG_OFFSET_CAPTURE);
						if ($result == 1) {
							$name = basename($chapter);
							$data = $datas[$name][$n];
							$data = str_replace('"', '\\"', $data);
							array_push($newfile, $matches[1][0] . str_replace(array("\r", "\n"), '', $data) . $matches[3][0] . LF);
							$n++;
						} else {
							array_push($newfile, $line);

							$z_exp = '/(?:GetGlobalFlag\(GCensor\) <= .\)\{ModCallScriptSection\(")(.*?)(?:",")(.*?)(?:")/';
							$result = preg_match($z_exp, $line, $matches, PREG_OFFSET_CAPTURE);
							if ($result == 1) {
								$parts = pathinfo($chapter);
								$in_chapter = $parts['dirname']."/".$matches[1][0].".txt";
								$in_handle = fopen($in_chapter, "r");
								$new_infile = [];

								if ($in_handle) {
									$state = false;
									$dialog_name = $matches[2][0];
									while (($in_line = fgets($in_handle)) !== false) {
										if (!$state) {
											if (str_contains($in_line, $dialog_name)) {
												$state = true;
											}
											array_push($new_infile, $in_line);
											continue;
										}

										$dialog_exp = '/dialog.*?\(/';
										$result = preg_match($dialog_exp, $in_line, $matches, PREG_OFFSET_CAPTURE);
										if ($result == 1) {
											array_push($new_infile, $in_line);
											$state = false;
											continue;
										}

										$result = preg_match($exp, $in_line, $matches, PREG_OFFSET_CAPTURE);
										if ($result == 1) {
											$name = basename($chapter);
											$data = $datas[$name][$n];
											$data = str_replace('"', '\\"', $data);
											array_push($new_infile, $matches[1][0] . str_replace(array("\r", "\n"), '', $data) . $matches[3][0] . LF);
											$n++;
										} else {
											array_push($new_infile, $in_line);
										}
									}
									fclose($in_handle);
								}
								file_put_contents($in_chapter, $new_infile);

							}

							
						}
					}
					fclose($handle);
				}
				file_put_contents($chapter, $newfile);
			}
			break;
		case 'insert_charnames':
			$story = readDirsAll($out_dir);
			$data = json_decode(file_get_contents($char_json_dir), true);

			for ($i = 0; $i < count($story); $i++) {
				$chapter = $story[$i];

				$exp = '/(\s"<color=.{0,7}>)(.*?)(<\/color>)/';

				$handle = fopen($chapter, "r");
				$newfile = [];
				if ($handle) {
					while (($line = fgets($handle)) !== false) {
						$result = preg_match($exp, $line, $matches, PREG_OFFSET_CAPTURE);
						if ($result == 1 && array_key_exists($matches[2][0], $data) && $data[$matches[2][0]] != '') {

							$newline = str_replace($matches[2][0], $data[$matches[2][0]] ,$line);

							array_push($newfile, $newline);
						} else {
							array_push($newfile, $line);
						}
					}
					fclose($handle);
				}
				file_put_contents($chapter, $newfile);
			}
			break;
		case 'extract_charnames':
			$story = readDirsAll($scripts_dir);
			
			$data = json_decode(file_get_contents($char_json_dir), true);
			
			for ($i = 0; $i < count($story); $i++) {
				$chapter = $story[$i];
				
				$exp = '/(\s"<color=.{0,7}>)(.*?)(<\/color>)/';
				
				$handle = fopen($chapter, "r");
				if ($handle) {
					while (($line = fgets($handle)) !== false) {
						$result = preg_match($exp, $line, $matches, PREG_OFFSET_CAPTURE);
						if ($result == 1 && !array_key_exists($matches[2][0], $data)) {
							$data[$matches[2][0]] = '';
						}
					}
					fclose($handle);
				}
			}
			ksort($data);
			file_put_contents($char_json_dir, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
			break;
	}
}


function recurseCopy(
    string $sourceDirectory,
    string $destinationDirectory,
    string $childFolder = ''
): void {
    $directory = opendir($sourceDirectory);

    if (is_dir($destinationDirectory) === false) {
        mkdir($destinationDirectory);
    }

    if ($childFolder !== '') {
        if (is_dir("$destinationDirectory/$childFolder") === false) {
            mkdir("$destinationDirectory/$childFolder");
        }

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir("$sourceDirectory/$file") === true) {
                recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
            } else {
                copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
            }
        }

        closedir($directory);

        return;
    }

    while (($file = readdir($directory)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        if (is_dir("$sourceDirectory/$file") === true) {
            recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$file");
        }
        else {
            copy("$sourceDirectory/$file", "$destinationDirectory/$file");
        }
    }

    closedir($directory);
}


main($argc, $argv);