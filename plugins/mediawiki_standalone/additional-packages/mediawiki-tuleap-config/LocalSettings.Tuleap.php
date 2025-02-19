<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

// Prevent password and realname change
$GLOBALS['wgGroupPermissions']['user']['editmyprivateinfo'] = false;

// Third Party Extensions - START ###
// ERM27085 - Extensions that were enabled in MediaWiki 1.23
wfLoadExtensions([
                     'CategoryTree',
                     'Cite',
                     'ImageMap',
                     'InputBox',
                     'LabeledSectionTransclusion',
                     'ParserFunctions',
                     'SyntaxHighlight_GeSHi',
                     'WikiEditor',
                     'PdfBook',
                 ]);
$GLOBALS['wgPFEnableStringFunctions'] = true;
$GLOBALS['wgPdfBookTab']              = true;

$GLOBALS['wgDefaultUserOptions']['usebetatoolbar']     = 1;
$GLOBALS['wgDefaultUserOptions']['usebetatoolbar-cgd'] = 1;
$GLOBALS['wgDefaultUserOptions']['wikieditor-preview'] = 1;
$GLOBALS['wgDefaultUserOptions']['wikieditor-publish'] = 1;

// Additional extensions
wfLoadExtensions([
                     'CodeEditor',
                     'Gadgets',
                     'MultimediaViewer',
                     'PageImages',
                     'PdfHandler',
                     'ReplaceText',
                     'Scribunto',
                     'TemplateData',
                     'TextExtracts',
                     'VisualEditor',
                 ]);
// Third Party Extensions - END ###

wfLoadExtension('Math');
$GLOBALS['wgMathValidModes']             = ['mathml'];
$GLOBALS['wgDefaultUserOptions']['math'] = 'mathml';
$GLOBALS['wgMaxShellMemory']             = 1228800;
$GLOBALS['wgHiddenPrefs'][]              = 'math';
$GLOBALS['wgMathoidCli']                 = [
    '/usr/lib/tuleap/mathoid/bin/mathoid-cli',
    '--config',
    '/usr/share/tuleap-mathoid/config.yaml',
];

// MediaWiki Core default settings - START ###
$GLOBALS['wgExternalLinkTarget'] = '_blank';
$GLOBALS['wgUrlProtocols'][]     = 'file://';
$GLOBALS['wgUrlProtocols'][]     = 'redis://'; // From old MediaWiki 1.23
// MediaWiki Core default settings - END ###

// Tuleap Specific - START ###
wfLoadExtension('TuleapIntegration');
$GLOBALS['wgTuleapOAuth2Config']['redirectUri']
    // "_oauth" is a virtual instance served by `Extension:TuleapWikifarm`
    = $GLOBALS['wgServer'] . '/mediawiki/_oauth/Special:TuleapLogin/callback';

wfLoadSkin('TuleapSkin');
// Tuleap Specific - END ###
