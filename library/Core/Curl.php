<?php


/**
 * Curl interface with random userAgent header and others
 */
class Core_Curl implements Core_Singleton_Interface {

	private static $_instance=NULL;

	protected $_userAgents=array(
		0=>'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:14.0) Gecko/20100101 Firefox/14.0.1',
		1=>'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:14.0) Gecko/20100101 Firefox/14.0.1',
		2=>'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:14.0) Gecko/20100101 Firefox/14.0.1',
		/*1=>'1st ZipCommander (Net) - http://www.zipcommander.com/',
		2=>'Ace Explorer',
		3=>'Activeworlds',
		4=>'ActiveWorlds/3.xx (xxx)',
		5=>'Advanced Browser (http://www.avantbrowser.com)',
		6=>'Akregator/1.2.9; librss/remnants',
		7=>'Alcatel-BG3/1.0 UP.Browser/5.0.3.1.2',
		8=>'AlertInfo 2.0 (Powered by Newsbrain)',
		9=>'amaya/x.xx libwww/x.x.x',
		10=>'Amiga-AWeb/3.4.167SE',
		11=>'AmigaVoyager/3.4.4 (MorphOS/PPC native)',
		12=>'Amoi 8512/R21.0 NF-Browser/3.3',
		13=>'annotate_google; http://ponderer.org/download/annotate_google.user.js',
		14=>'ANTFresco/x.xx',
		15=>'Aplix HTTP/1.0.1',
		16=>'Aplix_SANYO_browser/1.x (Japanese)',
		17=>'Aplix_SEGASATURN_browser/1.x (Japanese)',
		18=>'Apple iPhone v1.1.4 CoreMedia v1.0.0.4A102',
		19=>'Apple-PubSub/65.1.1',
		20=>'AU-MIC/2.0 MMP/2.0',
		21=>'AUDIOVOX-SMT5600',
		22=>'Avant Browser (http://www.avantbrowser.com)',
		23=>'AWeb',
		24=>'Barca/2.0.xxxx',
		25=>'BarcaPro/1.4.xxxx',
		26=>'Biyubi/x.x (Sistema Fenix; G11; Familia Toledo; es-mx)',
		27=>'BlockNote.Net',
		28=>'BlogBridge 2.13 (http://www.blogbridge.com/)',
		29=>'bluefish 0.6 HTML editor',
		30=>'CDR/1.7.1 Simulator/0.7(+http://timewe.net) Profile/MIDP-1.0 Configuration/CLDC-1.0',
		31=>'CERN-LineMode/2.15',
		32=>'Commerce Browser Center',
		33=>'contype',
		34=>'CoverScout%203/3.0.1 CFNetwork/339.5 Darwin/9.5.0 (i386) (iMac5,1)',
		35=>'Cuam Ver0.050bx',
		36=>'Cyberdog/2.0 (Macintosh; 68k)',
		37=>'Dillo/0.8.5-i18n-misc',
		38=>'Dillo/0.x.x',
		39=>'DocZilla/1.0 (Windows; U; WinNT4.0; en-US; rv:1.0.0) Gecko/20020804',
		40=>'DonutP; Windows98SE',
		41=>'Dragonfly File Reader',
		42=>'ELinks (0.x.x; Linux 2.4.20 i586; 132x60)',
		43=>'ELinks/0.x.x (textmode; NetBSD 1.6.2 sparc; 132x43)',
		44=>'endo/1.0 (Mac OS X; ppc i386; http://kula.jp/endo)',
		45=>'ExactSearch',
		46=>'Feedable/0.1 (compatible; MSIE 6.0; Windows NT 5.1)',
		47=>'FeedDemon/2.7 (http://www.newsgator.com/; Microsoft Windows XP)',
		48=>'Feedreader 3.xx (Powered by Newsbrain)',
		49=>'Feedshow/x.0 (http://www.feedshow.com; 1 subscriber)',
		50=>'FeedshowOnline (http://www.feedshow.com)',
		51=>'FeedZcollector v1.x (Platinum) http://www.feeds4all.com/feedzcollector',
		52=>'GenesisBrowser (HTTP 1.1; 0.9; XP SP2; .NET CLR 2.0.50727)',
		53=>'Google Talk',
		54=>'GreatNews/1.0',
		55=>'GreenBrowser',
		56=>'Haier-T10C/1.0 iPanel/2.0 WAP2.0 (compatible; UP.Browser/6.2.2.4; UPG1; UP/4.0; Embedded)',
		57=>'HotJava/1.0.1/JRE1.1.x',
		58=>'Hotzonu/x.0',
		59=>'httpunit/1.5',
		60=>'httpunit/1.x',
		61=>'IBrowse/2.2 (AmigaOS 3.5)',
		62=>'IBrowse/2.2 (Windows 3.1)',
		63=>'iCab/2.5.2 (Macintosh; I; PPC)',
		64=>'ICE Browser/5.05 (Java 1.4.0; Windows 2000 5.0 x86)',
		65=>'ImageVisu/v4.x.x',
		66=>'intraVnews/1.x',
		67=>'iSiloX/4.xx Windows/32',
		68=>'iTunes/x.x.x',
		69=>'Jakarta Commons-HttpClient/2.0xxx',
		70=>'Jakarta Commons-HttpClient/3.0-rcx',
		71=>'Java1.0.21.0',
		72=>'jBrowser/J2ME Profile/MIDP-1.0 Configuration/CLDC-1.0 (Google WAP Proxy/1.0)',
		73=>'Jeode/1.x.x',
		74=>'JetBrains Omea Reader 1.0.x (http://www.jetbrains.com/omea_reader/)',
		75=>'JetBrains Omea Reader 2.0 Release Candidate 1 (http://www.jetbrains.com/omea_reader/)',
		76=>'K-Meleon/0.6 (Windows; U; Windows NT 5.1; en-US; rv:0.9.5) Gecko/20011011',
		77=>'Kazehakase/0.x.x.[x]',
		78=>'Klondike/1.50 (WSP Win32) (Google WAP Proxy/1.0)',
		79=>'KWC-KX9/1109 UP.Browser/6.2.3.9.g.1.107 (GUI) MMP/2.0 UP.Link/6.3.0.0.0',
		80=>'LeapTag/0.8.1.beta081.r3750 (compatible; Mozilla 4.0; MSIE 5.5; robot@yoriwa.com)',
		81=>'LG-LX260 POLARIS-LX260/2.0 MMP/2.0 Profile/MIDP-2.0 Configuration/CLDC-1.1',
		82=>'LG/U8138/v1.0',
		83=>'Liferea/0.x.x (Linux; en_US.UTF-8; http://liferea.sf.net/)',
		84=>'Liferea/1.x.x (Linux; es_ES.UTF-8; http://liferea.sf.net/)',
		85=>'Links (0.9x; Linux 2.4.7-10 i686)',
		86=>'Links (0.9xpre12; Linux 2.2.14-5.0 i686; 80x24)',
		87=>'Links (2.xpre7; Linux 2.4.18 i586; x)',
		88=>'Lotus-Notes/4.5 ( Windows-NT )',
		89=>'Lunascape',
		90=>'lwp-trivial/1.35',
		91=>'lwp-trivial/1.35',
		92=>'Lynx/2-4-2 (Bobcat/0.5 [DOS] Jp Beta04)',
		93=>'Lynx/2.6 libwww-FM/2.14',
		94=>'Lynx/2.8 (;http://seebot.org)',
		95=>'Lynx/2.8.3dev.9 libwww-FM/2.14 SSL-MM/1.4.1 OpenSSL/0.9.6',
		96=>'MagpieRSS/0.7x (+http://magpierss.sf.net)',
		97=>'Media Player Classic',
		98=>'Microsoft Data Access Internet Publishing Provider DAV',
		99=>'MoonBrowser (version 0.41 Beta4)',
		100=>'Motorola-V3m Obigo',
		101=>'MovableType/x.x',
		102=>'Mozilla/1.1 (compatible; MSPIE 2.0; Windows CE)',
		103=>'Mozilla/1.10 [en] (Compatible; RISC OS 3.70; Oregano 1.10)',
		104=>'Mozilla/1.22 (compatible; MSIE 2.0d; Windows NT)',
		105=>'Mozilla/1.22 (compatible; MSIE 5.01; PalmOS 3.0) EudoraWeb 2',
		106=>'Mozilla/2.0',
		107=>'Mozilla/2.0 (compatible; AOL 3.0; Mac_PowerPC)',
		108=>'Mozilla/2.0 (Compatible; AOL-IWENG 3.0; Win16)',
		109=>'Mozilla/2.0 (compatible; MS FrontPage x.0)',
		110=>'Mozilla/2.0 (compatible; MSIE 2.1; Mac_PowerPC)',
		111=>'Mozilla/2.0 (compatible; MSIE 3.02; Update a; AK; Windows NT)',
		112=>'Mozilla/2.0 (compatible; MSIE 3.02; Update a; AOL 3.0; Windows 95)',
		113=>'Mozilla/2.0 (compatible; MSIE 3.0; AK; Windows 95)',
		114=>'Mozilla/2.0 (compatible; MSIE 3.0; Windows 3.1)',
		115=>'Mozilla/2.0 (compatible; MSIE 3.0B; Win32)',
		116=>'Mozilla/2.01 (Win16; I)',
		117=>'Mozilla/2.02Gold (Win95; I)',
		118=>'Mozilla/3.0 (compatible; AvantGo 3.2)',
		119=>'Mozilla/3.0 (compatible; NetPositive/2.2)',
		120=>'Mozilla/3.0 (compatible; Opera/3.0; Windows 3.1) v3.1',
		121=>'Mozilla/3.0 (compatible; Opera/3.0; Windows 95/NT4) 3.2',
		122=>'Mozilla/3.0 (Planetweb/2.100 JS SSL US; Dreamcast US)',
		123=>'Mozilla/3.0 (Win16; I)',
		124=>'Mozilla/3.0 (Win95; I)',
		125=>'Mozilla/3.0 (WinNT; I)',
		126=>'Mozilla/3.0 (WorldGate Gazelle 3.5.1 build 11; FreeBSD2.2.8-STABLE)',
		127=>'Mozilla/3.0 (X11; I; OSF1 V4.0 alpha)',
		128=>'Mozilla/3.0 NAVIO_AOLTV (11; 13; Philips; PH200; 1; R2.0C36_AOL.0110OPTIK; R2.0.0139d_OPTIK)',
		129=>'Mozilla/3.0 WebTV/1.2 (compatible; MSIE 2.0)',
		130=>'Mozilla/3.01 (compatible; AmigaVoyager/2.95; AmigaOS/MC680x0)',
		131=>'Mozilla/3.01 (compatible; Netbox/3.5 R92; Linux 2.2)',
		132=>'Mozilla/3.01-C-MACOS8 (Macintosh; I; PPC)',
		133=>'Mozilla/3.01Gold (X11; I; Linux 2.0.32 i486)',
		134=>'Mozilla/3.01Gold (X11; I; SunOS 5.5.1 sun4m)',
		135=>'Mozilla/3.01SGoldC-SGI (X11; I; IRIX 6.3 IP32)',
		136=>'Mozilla/3.04 (compatible; ANTFresco/2.13; RISC OS 4.02)',
		137=>'Mozilla/3.04 (compatible; NCBrowser/2.35; ANTFresco/2.17; RISC OS-NC 5.13 Laz1UK1309)',
		138=>'Mozilla/3.04 (compatible;QNX Voyager 2.03B ;Photon)',
		139=>'Mozilla/3.x (I-Opener 1.1; Netpliance)',
		140=>'Mozilla/4.0 (compatible; ibisBrowser)',
		141=>'Mozilla/4.0 (compatible; Lotus-Notes/5.0; Windows-NT)',
		142=>'Mozilla/4.0 (compatible; MSIE 4.01; AOL 4.0; Windows 98)',
		143=>'Mozilla/4.0 (compatible; MSIE 4.01; Mac_PowerPC)',
		144=>'Mozilla/4.0 (compatible; MSIE 4.01; Windows 95)',
		145=>'Mozilla/4.0 (compatible; MSIE 4.01; Windows CE; MSN Companion 2.0; 800x600; Compaq)',
		146=>'Mozilla/4.0 (compatible; MSIE 4.01; Windows CE; PPS; 240x320)',
		147=>'Mozilla/4.0 (compatible; MSIE 4.01; Windows NT Windows CE)',
		148=>'Mozilla/4.0 (compatible; MSIE 4.01; Windows NT)',
		149=>'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; NetCaptor 6.5.0RC1)',
		150=>'Mozilla/4.0 (compatible; MSIE 5.0; AOL 5.0; Windows 95; DigExt; Gateway2000; sureseeker.com)',
		151=>'Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC; AtHome021)',
		152=>'Mozilla/4.0 (compatible; MSIE 5.0; Windows ME) Opera 5.11 [en]',
		153=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows 98; Crazy Browser 1.x.x)',
		154=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows 98; KITV4.7 Wanadoo)',
		155=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows 98; SAFEXPLORER TL)',
		156=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows 98; SYMPA; Katiesoft 7; SimulBrowse 3.0)',
		157=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows 98; Win 9x 4.90; BTinternet V8.1)',
		158=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0) Active Cache Request',
		159=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0; .NET CLR 1.0.3705)',
		160=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0; AIRF)',
		161=>'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0; N_o_k_i_a)',
		162=>'Mozilla/4.0 (compatible; MSIE 6.0; AOL 9.0; Windows NT 5.1; SV1; HbTools 4.7.2)',
		163=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows 98; Net M@nager V3.02 - www.vinn.com.au)',
		164=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; .NET CLR 1.1.4322; Lunascape 2.1.3)',
		165=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Deepnet Explorer)',
		166=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Hotbar 3.0)',
		167=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; iOpus-I-M)',
		168=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; KKman3.0)',
		169=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; MathPlayer2.0)',
		170=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Maxthon) ',
		171=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; PeoplePal 3.0; MSIECrawler)',
		172=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Q312461; IOpener Release 1.1.04)',
		173=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SIMBAR Enabled; InfoPath.1)',
		174=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; StumbleUpon.com 1.760; .NET CLR 1.1.4322)',
		175=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1;  Embedded Web Browser from: http://bsalsa.com/; MSIECrawler)',
		176=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)',
		177=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; DX-Browser 5.0.0.0)',
		178=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; FunWebProducts; ezPeer+ v1.0 Beta (0.4.1.98); ezPeer+ v1.0 (0.5.0.00); .NET CLR 1.1.4322; MSIECrawler)',
		179=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; MRA 4.3 (build 01218))',
		180=>'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; bgft)',
		181=>'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; GTB5; User-agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; http://bsalsa.com) ; .NET CLR 2.0.50727)',
		182=>'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Tablet PC 2.0)',
		183=>'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET CLR 3.0.04506.30; .NET CLR 3.0.04506.648)',
		184=>'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)',
		185=>'Mozilla/4.0 (compatible; Opera/3.0; Windows 4.10) 3.51 [en]',
		186=>'Mozilla/4.0 (compatible; RSS Popper)',
		187=>'Mozilla/4.0 (compatible; SiteKiosk 4.0; MSIE 5.0; Windows 98; SiteCoach 1.0)',
		188=>'Mozilla/4.0 (MobilePhone PM-8200/US/1.0) NetFront/3.x MMP/2.0',
		189=>'Mozilla/4.0 WebTV/2.6 (compatible; MSIE 4.0)',
		190=>'Mozilla/4.02 [en] (X11; I; SunOS 5.6 sun4u)',
		191=>'Mozilla/4.04 [en] (X11; I; HP-UX B.10.20 9000/712)',
		192=>'Mozilla/4.04 [en] (X11; I; IRIX 5.3 IP22)',
		193=>'Mozilla/4.05 (Macintosh; I; 68K Nav)',
		194=>'Mozilla/4.05 (Macintosh; I; PPC Nav)',
		195=>'Mozilla/4.05 [en] (X11; I; SunOS 4.1.4 sun4m)',
		196=>'Mozilla/4.08 [en] (WinNT; U)',
		197=>'Mozilla/4.5 (compatible; iCab 2.5.3; Macintosh; I; PPC)',
		198=>'Mozilla/4.5 (compatible; OmniWeb/4.0.5; Mac_PowerPC)',
		199=>'Mozilla/4.5 (compatible; OmniWeb/4.1-beta-1; Mac_PowerPC)',
		200=>'Mozilla/4.5 [en]C-CCK-MCD {RuralNet} (Win98; I)',
		201=>'Mozilla/4.5b1 [en] (X11; I; Linux 2.0.35 i586)',
		202=>'Mozilla/4.61 [de] (OS/2; I)',
		203=>'Mozilla/4.61 [en] (X11; U; ) - BrowseX (2.0.0 Windows)',
		204=>'Mozilla/4.7 (compatible; OffByOne; Windows 98) Webster Pro V3.2',
		205=>'Mozilla/4.72C-CCK-MCD Caldera Systems OpenLinux [en] (X11; U; Linux 2.2.14 i686)',
		206=>'Mozilla/4.75C-ja [ja] (X11; U; OSF1 V5.1 alpha)',
		207=>'Mozilla/4.76 (Windows 98; U) Opera 5.12 [en]',
		208=>'Mozilla/4.76 [en] (X11; U; FreeBSD 4.4-STABLE i386)',
		209=>'Mozilla/4.76 [en] (X11; U; SunOS 5.7 sun4u)',
		210=>'Mozilla/4.77C-SGI [en] (X11; U; IRIX 6.5 IP32)',
		211=>'Mozilla/5.0 (compatible) GM RSS Panel X',
		212=>'Mozilla/5.0 (compatible; Konqueror/2.0.1; X11); Supports MD5-Digest; Supports gzip encoding',
		213=>'Mozilla/5.0 (compatible; Konqueror/2.1.1; X11)',
		214=>'Mozilla/5.0 (compatible; Konqueror/2.2.2)',
		215=>'Mozilla/5.0 (compatible; Konqueror/2.2.2; Linux 2.4.14-xfs; X11; i686)',
		216=>'Mozilla/5.0 (compatible; SnapPreviewBot; en-US; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9',
		217=>'Mozilla/5.0 (compatible;FindITAnswersbot/1.0;+http://search.it-influentials.com/bot.htm)',
		218=>'Mozilla/5.0 (Gecko/20070310 Mozshot/0.0.20070628; http://mozshot.nemui.org/)',
		219=>'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.4; en-US; rv:1.9b5) Gecko/2008032619 Firefox/3.0b5',
		220=>'Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.0.1) Gecko/20021219 Chimera/0.6 ',
		221=>'Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.0.1) Gecko/20030306 Camino/0.7',
		222=>'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-US) AppleWebKit/xx (KHTML like Gecko) OmniWeb/v5xx.xx',
		223=>'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-us) AppleWebKit/xxx.x (KHTML like Gecko) Safari/12x.x',
		224=>'Mozilla/5.0 (Macintosh; U; PPC; en-US; rv:0.9.2) Gecko/20010726 Netscape6/6.1',
		225=>'Mozilla/5.0 (Sage)',
		226=>'Mozilla/5.0 (SunOS 5.8 sun4u; U) Opera 5.0 [en]',
		227=>'Mozilla/5.0 (Windows; U; Win98; en-US; rv:0.9.2) Gecko/20010726 Netscape6/6.1',
		228=>'Mozilla/5.0 (Windows; U; Win98; en-US; rv:x.xx) Gecko/20030423 Firebird Browser/0.6',
		229=>'Mozilla/5.0 (Windows; U; Win9x; en; Stable) Gecko/20020911 Beonex/0.8.1-stable',
		230=>'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/0.2.153.1 Safari/525.19',
		231=>'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.5) Gecko/20060731 Firefox/1.5.0.5 Flock/0.7.4.1',
		232=>'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008092215 Firefox/3.0.1 Orca/1.1 beta 3',
		233=>'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:x.x.x) Gecko/20041107 Firefox/x.x',
		234=>'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:x.xx) Gecko/20030504 Mozilla Firebird/0.6',
		235=>'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:x.xxx) Gecko/20041027 Mnenhy/0.6.0.104',
		236=>'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9b5) Gecko/2008032620 Firefox/3.0b5',
		237=>'Mozilla/5.0 (Windows; U;XMPP Tiscali Communicator v.10.0.1; Windows NT 5.1; it; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3',
		238=>'Mozilla/5.0 (X11; Linux i686; U;rv: 1.7.13) Gecko/20070322 Kazehakase/0.4.4.1',
		239=>'Mozilla/5.0 (X11; U; Linux 2.4.2-2 i586; en-US; m18) Gecko/20010131 Netscape6/6.01',
		240=>'Mozilla/5.0 (X11; U; Linux i686; de-AT; rv:1.8.0.2) Gecko/20060309 SeaMonkey/1.0',
		241=>'Mozilla/5.0 (X11; U; Linux i686; en-GB; rv:1.7.6) Gecko/20050405 Epiphany/1.6.1 (Ubuntu) (Ubuntu package 1.0.2)',
		242=>'Mozilla/5.0 (X11; U; Linux i686; en-US; Nautilus/1.0Final) Gecko/20020408',
		243=>'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:0.9.3) Gecko/20010801',
		244=>'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2b) Gecko/20021007 Phoenix/0.3',
		245=>'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.6) Gecko/20040413 Epiphany/1.2.1',
		246=>'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.7) Gecko/20060909 Firefox/1.5.0.7 SnapPreviewBot',
		247=>'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1) Gecko/20061129 BonEcho/2.0',
		248=>'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.1) Gecko/20061205 Iceweasel/2.0.0.1 (Debian-2.0.0.1+dfsg-2)',
		249=>'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9a8) Gecko/2007100619 GranParadiso/3.0a8',
		250=>'Mozilla/5.0 Galeon/1.0.2 (X11; Linux i686; U;) Gecko/20011224',
		251=>'MSFrontPage/4.0',
		252=>'NETCOMplete/x.xx',
		253=>'NetNewsWire/2.x (Mac OS X; http://ranchero.com/netnewswire/)',
		254=>'NewsGator FetchLinks extension/0.2.0 (http://graemef.com)',
		255=>'NewsGatorOnline/2.0 (http://www.newsgator.com; 1 subscribers)',
		256=>'NSPlayer/10.0.0.xxxx WMFSDK/10.0',
		257=>'Opera/5.0 (Linux 2.0.38 i386; U) [en]',
		258=>'Opera/5.11 (Windows ME; U) [ru]',
		259=>'Opera/5.12 (Windows 98; U) [en]',
		260=>'Opera/6.x (Linux 2.4.8-26mdk i686; U) [en]',
		261=>'Opera/6.x (Windows NT 4.0; U) [de]',
		262=>'Opera/7.x (Windows NT 5.1; U) [en]',
		263=>'Opera/8.xx (Windows NT 5.1; U; en)',
		264=>'Opera/9.0 (Windows NT 5.1; U; en)',
		265=>'Opera/9.00 (Windows NT 5.1; U; de)',
		266=>'Opera/9.60 (Windows NT 5.1; U; de) Presto/2.1.1',
		267=>'OPWV-SDK UP.Browser/7.0.2.3.119 (GUI) MMP/2.0 Push/PO',
		268=>'Orca Browser (http://www.orcabrowser.com)',
		269=>'Plagger/0.x.xx (http://plagger.org/)',
		270=>'portalmmm/2.0 S500i(c20;TB)',
		271=>'Quicksilver (Blacktree,MacOSX)',
		272=>'QuickTime\xaa.7.0.4 (qtver=7.0.4;cpu=PPC;os=Mac 10.3.9)',
		273=>'REBOL View 1.x.x.x.x',
		274=>'Rome Client (http://tinyurl.com/64t5n) Ver: 0.9',
		275=>'RssBandit/1.5.0.10 (.NET CLR 1.1.4322.2407; WinNT 5.1.2600.0; http://www.rssbandit.org) (.NET CLR 1.1.4322.2407; WinNT 5.1.2600.0; )',
		276=>'RSSOwl/1.2.3 2006-11-26 (Windows; U; zhtw)',
		277=>'RSSOwl/1.2.4 Preview Release 2007-04-15 (Windows; U; zhtw)',
		278=>'RssReader/1.0.xx.x (http://www.rssreader.com) Microsoft Windows NT 5.1.2600.0',
		279=>'RX Bar',
		280=>'Science Traveller International 1X/1.0',
		281=>'Scope (Mars+)',
		282=>'SimpleFavPanel/1.2',
		283=>'Sleipnir',
		284=>'Sleipnir Version 1.xx',
		285=>'Sleipnir Version2.x',
		286=>'Sleipnir/2.xx',
		287=>'SlimBrowser',
		288=>'Snarfer/0.x.x (http://www.snarfware.com/)',
		289=>'SoftBank/1.0/812SH/SHJ001 Browser/NetFront/3.3 Profile/MIDP-2.0 Configuration/CLDC-1.1',
		290=>'Sunrise XP/2.x',
		291=>'Sunrise/0.42g (Windows XP)',
		292=>'SWB/V1.4 (HP)',
		293=>'Sylera/1.2.x',
		294=>'Syndirella/0.91pre',
		295=>'T-Online Browser',
		296=>'UCmore',
		297=>'UCMore Crawler App',
		298=>'UCWEB5.1',
		299=>'UP.Browser/3.01-IG01 UP.Link/3.2.3.4',
		300=>'UPG1 UP/4.0 (compatible; Blazer 1.0)',
		301=>'Visicom Toolbar',
		302=>'VLC media player - version 0.8.5 Janus - (c) 1996-2006 the VideoLAN team',
		303=>'W3CLineMode/5.4.0 libwww/5.x.x',
		304=>'w3m/0.x.xx',
		305=>'WannaBe (Macintosh; PPC)',
		306=>'WapOnWindows 1.0',
		307=>'Windows-Media-Player/10.00.00.xxxx',
		308=>'WinPodder (http://winpodder.com)',
		309=>'WinWAP/3.x (3.x.x.xx; Win32) (Google WAP Proxy/1.0)',
		310=>'WordPress/x.x.x.x PHP/4.x.xx',
		311=>'xine/1.0',
		312=>'Y!TunnelPro',
		313=>'YTunnelPro',
		314=>'Zoo Tycoon 2 Client -- http://www.zootycoon.com',*/
	);
	private $_agentsCount=0;
	private $_responce='';
	private $_header='';
	private $_error='';
	private $_withCookie=false;
	private $_withHeader=false;

	public function __construct() {
		$this->_agentsCount=count( $this->_userAgents );
	}

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Core_Curl();
		}
		self::$_instance->init();
		return self::$_instance;
	}

	public function init() {
		$this->_responce=$this->_error='';
		$this->_withCookie=false;
		$this->_withHeader=false;
	}
	
	public function withCookie( $_strUrl='' ) {
		$this->_withCookie = $_strUrl;
		return $this;
	}	
	public function withHeader() {
		$this->_withHeader = true;
		return $this;
	}

	public function getResponce() {
		return $this->_responce;
	}
	
	public function getError() {
		return $this->_error;
	}

	private $_post=false;
	
	public function setPost($_mix){
		if ( is_array($_mix) ){
			foreach ($_mix as $_k=>$_v){
				if ( is_array($_v)||is_object($_v) ){
					$this->_post .= ( (empty($this->_post) ) ? '' : '&' ).$_k.'='.urlencode(serialize($_v));
				} else {
					$this->_post .= ( (empty($this->_post) ) ? '' : '&' ).$_k.'='.urlencode($_v);
				}
			}
		} else {
			$this->_post=$_mix;
		}
		return $this;
	}
	
	public function getContent( $_strUrl='' ) {
		$this->_responce='';

		if( !function_exists("curl_init")||empty( $_strUrl ) ) {
			return false;
		}
		$ch = curl_init();
		$_cookieFilePath = tempnam ("/tmp", "CURLCOOKIE");
		if ( $this->_withCookie ){
			curl_setopt($ch, CURLOPT_HEADER, $this->_withHeader);
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_AUTOREFERER, 0);
			curl_setopt($ch, CURLOPT_REFERER, $this->_withCookie);
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $_cookieFilePath);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $_cookieFilePath);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
			curl_setopt($ch, CURLOPT_COOKIE, session_name() . "=" . session_id());
			curl_setopt($ch, CURLOPT_URL, $this->_withCookie);
			curl_exec ($ch);
		}
		
		curl_setopt($ch, CURLOPT_URL, $_strUrl );
		curl_setopt($ch, CURLOPT_USERAGENT, $this->getRandAgent() );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Cache-Control: max-age=0",
			"Connection: keep-alive",
			"Keep-Alive: 300",
			"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
			"Accept-Language: en-us,en;q=0.5",
		));
		if ( $this->_withCookie ){
			curl_setopt($ch, CURLOPT_COOKIEFILE, $_cookieFilePath);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $_cookieFilePath);
		}
		//curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com' );
		//curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		if( stripos($_strUrl,'https://')!==false ){
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
		}
		if ( !empty($this->_post)){
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->_post );			
		}
		$this->_responce=curl_exec($ch);
		if ( $this->_withCookie ){
			$this->_header=substr( $this->_responce,0, curl_getinfo($ch,CURLINFO_HEADER_SIZE));
			$this->_responce=substr( $this->_responce, curl_getinfo($ch,CURLINFO_HEADER_SIZE));
		}
		$this->_error=curl_error($ch);
		curl_close ($ch);
		return !empty( $this->_responce );
	}

	public function getCookie() {
		preg_match('/^Set-Cookie: (.*?);/m', $this->_header, $_arr);
		if (isset($_arr[1])){
			return $_arr[1];
		}
		return '';
	}
	
	private function getRandAgent() {
		srand ((double)microtime()*1000000);
		return $this->_userAgents[(rand(0, $this->_agentsCount-1))];
	}

	public static function getResult( &$strRes, $_strFile='' ) {
		if ( empty( $_strFile ) ) {
			return false;
		}
		$_objCurl=self::getInstance();
		if ( !$_objCurl->getContent( $_strFile ) ) {
			return false;
		}
		$strRes=$_objCurl->getResponce();
		return true;
	}
	
	public static function async( $url, $params, $type='POST' ){
		foreach( $params as $key => &$val ){
			if( is_array($val) ){
				$val=implode(',', $val);
			}
			$postParams[]=$key.'='.urlencode($val);
		}
		$postString=implode('&', $postParams);
		$parts=parse_url($url);
		$fp=fsockopen( $parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30 );
		if('GET' == $type){
			$parts['path'] .= '?'.$postString;
		}
		$out=$type.' '.$parts['path'].' HTTP/1.1'."\r\n";
		$out.='Host: '.$parts['host']."\r\n";
		$out.='Content-Type: application/x-www-form-urlencoded'."\r\n";
		$out.='Content-Length: '.strlen($postString)."\r\n";
		$out.='Connection: Close'."\r\n\r\n";
		if( 'POST' == $type && isset($postString) ){
			$out.= $postString;
		}
		fwrite($fp, $out);
		fclose($fp);
	}
  
}
?>