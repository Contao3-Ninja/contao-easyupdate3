<?php

/**
 * TYPOlight webCMS
 * Copyright (C) 2005-2009 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 * PHP version 5
 * @copyright	Copyright easyupdate 2009
 * @author		Lutz Schoening
 * @author		Glen Langer - offline fork 
 * @package		easyupdate
 * @license		LGPL
 */
$GLOBALS['TL_LANG']['MOD']['easyupdate3'] = array (
	'easyUpdate3',
	'Installieren Sie eine neue Contao 3 Version aus dem Backend heraus.'
);
$GLOBALS['TL_LANG']['easyupdate3']['backBT'] = 'Zurück';
$GLOBALS['TL_LANG']['easyupdate3']['headline'] = 'easyUpdate einer Contao-Version (aktuelle Version: %s)';
$GLOBALS['TL_LANG']['easyupdate3']['selectfile'] = 'Bitte wählen Sie ein Archiv aus (ZIP-Datei)';
$GLOBALS['TL_LANG']['easyupdate3']['description'] = 'Wählen Sie hier ein Archiv aus, welches im Verzeichnis (tl_files/easyupdate) abgelegt werden muss (ev. vorhandene Unterverzeichnisse werden nicht durchsucht). Es werden nur ZIP-Dateien unterstützt.';
$GLOBALS['TL_LANG']['easyupdate3']['setfile'] = 'Auswählen';
$GLOBALS['TL_LANG']['easyupdate3']['files']['original'] = 'Originaldateien';
$GLOBALS['TL_LANG']['easyupdate3']['files']['backup'] = 'Backupdateien';
$GLOBALS['TL_LANG']['easyupdate3']['noupdate'] = 'Dateien vom Update ausschließen';
$GLOBALS['TL_LANG']['easyupdate3']['demo'] = 'Demo Dateien';
$GLOBALS['TL_LANG']['easyupdate3']['config_legend'] = 'Config Dateien';
$GLOBALS['TL_LANG']['easyupdate3']['other_legend'] = 'andere Dateien';
$GLOBALS['TL_LANG']['easyupdate3']['all'] = 'Alle auswählen';
$GLOBALS['TL_LANG']['easyupdate3']['noupdatetext'] = 'Die hier ausgewählten Dateien werden beim Aktualisieren nicht überschrieben bzw überspielt. In das Backup werde Sie aber trotzdem übernommen.';
$GLOBALS['TL_LANG']['easyupdate3']['updatex'] = 'Version (%s) ======> Version (%s)';
$GLOBALS['TL_LANG']['easyupdate3']['update0'] = 'Sie versuchen, eine ältere Version zu installieren. Dies ist zwar nicht ausgeschlossen, jedoch nicht empfehlenswert und auch nicht immer möglich. Es kann zu Problemen beim Login führen, da ab der TYPOlight-Version 2.7 die Passwörter zusätzlich gesalzen werden.';
$GLOBALS['TL_LANG']['easyupdate3']['update1'] = 'Sie versuchen, eine neuere Version zu installieren.';
$GLOBALS['TL_LANG']['easyupdate3']['update2'] = 'Sie versuchen, die gleiche Version zu installieren. Sind Sie sich sicher?';
$GLOBALS['TL_LANG']['easyupdate3']['next'] = 'Nächsten Schritt ausführen';
$GLOBALS['TL_LANG']['easyupdate3']['previous'] = 'Zurück zum Hauptbildschirm';
$GLOBALS['TL_LANG']['easyupdate3']['settings'] = 'Einstellungen';
$GLOBALS['TL_LANG']['easyupdate3']['changelog']['headline'] = 'Changelog zwischen der Version %s und %s';
$GLOBALS['TL_LANG']['easyupdate3']['changelog']['same'] = 'Sie installieren die gleiche Version, daher auch keine Änderungen.';
$GLOBALS['TL_LANG']['easyupdate3']['changelog']['no'] = 'Der Changelog ist leider nicht lesbar. Weitere Informationen zu den Änderungen können Sie auf der Contao-Homepage (http://www.contao.org/) nachlesen.';
$GLOBALS['TL_LANG']['easyupdate3']['content'] = 'Inhalt der Archiv-Datei';
$GLOBALS['TL_LANG']['easyupdate3']['backup'] = 'Sicherung der aktuellen Dateien';
$GLOBALS['TL_LANG']['easyupdate3']['backuped'] = 'Gesichert: ';
$GLOBALS['TL_LANG']['easyupdate3']['update'] = 'Aktualisierung der Dateien';
$GLOBALS['TL_LANG']['easyupdate3']['updated'] = 'Aktualisiert: ';
$GLOBALS['TL_LANG']['easyupdate3']['skipped'] = 'Übersprungen: ';
$GLOBALS['TL_LANG']['easyupdate3']['error'] = 'Fehler:';
$GLOBALS['TL_LANG']['easyupdate3']['exclude'] = 'auschlossen';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['headline'] = 'Bitte aufmerksam und vollständig vor der Benutzung lesen';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text1'] = '<h2>Die Installation besteht aus vier verschiedenen Schritten:</h2> 
														<ul><li>Auflistung der Dateien im Archiv, welches importiert werden soll</li>
														<li>Backup der aktuellen Dateien, damit man diese im Fehlerfall wiederherstellen kann</li>
														<li>Aktualisierung der erforderlichen Dateien</li>
														<li>Ausführung des Install-Tools</li></ul>
														Für den 4. Schritt wird das Install-Tool verwendet. Es kann vorkommen, dass die Datenbank aktualisiert muss; darauf wird man im Install-Tool hingewiesen.														
														Bitte beachten: Sofern die Config-Dateien überschrieben wurden, lautet das Passwort des Install-Tools "contao".';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text2'] = '<h2>Sicherheitshinweise:</h2>
														<ul><li>Es gibt keine 100%-ige Sicherheit, dass alles einwandfrei funktioniert. Seien Sie sich also bewusst, was Sie tun!</li>
														<li>Machen Sie bei der ersten Verwendung dieses Tools ruhig eine zusätzliche Sicherung der Datenbank.</li>
														<li>Das Tool greift auf das Dateisystem zu, das kann zu DATENVERLUST führen!</li>
														<li>Alles was Sie machen, tun Sie auf eigene Gefahr.</li></ul>';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['left'] = '<h2>Getestete und funktionierende Vorgänge:</h2>
																<ul><li>3.0.0 ====> 3.0.1</li>
																</ul>';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['right'] = '<h2>Getestete und fehlerhafte Vorgänge:</h2>
																<ul><li>2.x.x ====> 2.x.x</li></ul>';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text4'] = 'Getestet wurde eine Installation bis zur Contao Version 3.0.1. Nicht mit Contao 2.x möglich!
														Wenn eine Versionsnummer X.X.X erscheint, dann ist wahrscheinlich die ZIP-Datei beschädigt.';
