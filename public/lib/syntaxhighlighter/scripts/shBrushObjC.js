/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/SyntaxHighlighter
 *
 * SyntaxHighlighter is donationware. If you are using it, please donate.
 * http://alexgorbatchev.com/SyntaxHighlighter/donate.html
 *
 * @version
 * 3.0.83 (July 02 2010)
 * 
 * @copyright
 * Copyright (C) 2004-2010 Alex Gorbatchev.
 *
 * @license
 * Dual licensed under the MIT and GPL licenses.
 */
;(function()
{
	// CommonJS
	typeof(require) != 'undefined' ? SyntaxHighlighter = require('shCore').SyntaxHighlighter : null;

	function Brush()
	{
		// Copyright 2006 Shin, YoungJin
	
		var datatypes =	'ATOM BOOL BOOLEAN BYTE CHAR COLORREF DWORD DWORDLONG DWORD_PTR ' +
						'DWORD32 DWORD64 FLOAT HACCEL HALF_PTR HANDLE HBITMAP HBRUSH ' +
						'HCOLORSPACE HCONV HCONVLIST HCURSOR HDC HDDEDATA HDESK HDROP HDWP ' +
						'HENHMETAFILE HFILE HFONT HGDIOBJ HGLOBAL HHOOK HICON HINSTANCE HKEY ' +
						'HKL HLOCAL HMENU HMETAFILE HMODULE HMONITOR HPALETTE HPEN HRESULT ' +
						'HRGN HRSRC HSZ HWINSTA HWND INT INT_PTR INT32 INT64 LANGID LCID LCTYPE ' +
						'LGRPID LONG LONGLONG LONG_PTR LONG32 LONG64 LPARAM LPBOOL LPBYTE LPCOLORREF ' +
						'LPCSTR LPCTSTR LPCVOID LPCWSTR LPDWORD LPHANDLE LPINT LPLONG LPSTR LPTSTR ' +
						'LPVOID LPWORD LPWSTR LRESULT PBOOL PBOOLEAN PBYTE PCHAR PCSTR PCTSTR PCWSTR ' +
						'PDWORDLONG PDWORD_PTR PDWORD32 PDWORD64 PFLOAT PHALF_PTR PHANDLE PHKEY PINT ' +
						'PINT_PTR PINT32 PINT64 PLCID PLONG PLONGLONG PLONG_PTR PLONG32 PLONG64 POINTER_32 ' +
						'POINTER_64 PSHORT PSIZE_T PSSIZE_T PSTR PTBYTE PTCHAR PTSTR PUCHAR PUHALF_PTR ' +
						'PUINT PUINT_PTR PUINT32 PUINT64 PULONG PULONGLONG PULONG_PTR PULONG32 PULONG64 ' +
						'PUSHORT PVOID PWCHAR PWORD PWSTR SC_HANDLE SC_LOCK SERVICE_STATUS_HANDLE SHORT ' +
						'SIZE_T SSIZE_T TBYTE TCHAR UCHAR UHALF_PTR UINT UINT_PTR UINT32 UINT64 ULONG ' +
						'ULONGLONG ULONG_PTR ULONG32 ULONG64 USHORT USN VOID WCHAR WORD WPARAM WPARAM WPARAM ' +
						'char bool short int __int32 __int64 __int8 __int16 long float double __wchar_t ' +
						'clock_t _complex _dev_t _diskfree_t div_t ldiv_t _exception _EXCEPTION_POINTERS ' +
						'FILE _finddata_t _finddatai64_t _wfinddata_t _wfinddatai64_t __finddata64_t ' +
						'__wfinddata64_t _FPIEEE_RECORD fpos_t _HEAPINFO _HFILE lconv intptr_t ' +
						'jmp_buf mbstate_t _off_t _onexit_t _PNH ptrdiff_t _purecall_handler ' +
						'sig_atomic_t size_t _stat __stat64 _stati64 terminate_function ' +
						'time_t __time64_t _timeb __timeb64 tm uintptr_t _utimbuf ' +
						'va_list wchar_t wctrans_t wctype_t wint_t signed uint32_t uint16_t ' +
                        'unsigned id uint8_t void dispatch_.*_t';

		var keywords =	'break case catch class const __finally __exception __try ' +
						'const_cast continue private public protected __declspec ' +
						'default delete deprecated dllexport dllimport do dynamic_cast ' +
						'else enum explicit extern if for friend goto inline ' +
						'mutable naked namespace new noinline noreturn nothrow ' +
						'register reinterpret_cast selectany synthesize ' +
						'sizeof static static_cast struct switch template this ' +
						'thread throw true false try typedef typeid typename union ' +
						'using uuid virtual volatile whcar_t while property interface ' +
                        'end implementation return selector autoreleasepool';
					
		var functions =	'assert isalnum isalpha iscntrl isdigit isgraph islower isprint' +
						'ispunct isspace isupper isxdigit tolower toupper errno localeconv ' +
						'setlocale acos asin atan atan2 ceil cos cosh exp fabs floor fmod ' +
						'frexp ldexp log log10 modf pow sin sinh sqrt tan tanh jmp_buf ' +
						'longjmp setjmp raise signal sig_atomic_t va_arg va_end va_start ' +
						'clearerr fclose feof ferror fflush fgetc fgetpos fgets fopen ' +
						'fprintf fputc fputs fread freopen fscanf fseek fsetpos ftell ' +
						'fwrite getc getchar gets perror printf putc putchar puts remove ' +
						'rename rewind scanf setbuf setvbuf sprintf sscanf tmpfile tmpnam ' +
						'ungetc vfprintf vprintf vsprintf abort abs atexit atof atoi atol ' +
						'bsearch calloc div exit free getenv labs ldiv malloc mblen mbstowcs ' +
						'mbtowc qsort rand realloc srand strtod strtol strtoul system ' +
						'wcstombs wctomb memchr memcmp memcpy memmove memset strcat strchr ' +
						'strcmp strcoll strcpy strcspn strerror strlen strncat strncmp ' +
						'strncpy strpbrk strrchr strspn strstr strtok strxfrm asctime ' +
						'clock ctime difftime gmtime localtime mktime strftime time';
		var special = 'self super size nil YES NO NULL';

		this.regexList = [
			{ regex: SyntaxHighlighter.regexLib.singleLineCComments,	css: 'comments color5 italic' },			// one line comments
			{ regex: SyntaxHighlighter.regexLib.multiLineCComments,		css: 'comments color5 italic' },			// multiline comments
			{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string color9' },			// strings
			{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string color9' },			// strings
			{ regex: /^ *#.*/gm,										css: 'preprocessor' },
			{ regex: new RegExp(this.getKeywords(datatypes), 'gm'),		css: 'color6 bold' },
			{ regex: new RegExp(this.getKeywords(functions), 'gm'),		css: 'functions bold color8' },
			{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'plain bold' },
			{ regex: new RegExp(this.getKeywords(special), 'gm'),		css: 'color7' },
            { regex: /\s[\d\.\,]+(?=\s|;|\)|\(|\-|\=|\+)/gm,            css: 'color7' },
            { regex: /NS[A-Za-z_0-9]+/gm,                               css: 'color5' },
            { regex: /(\s+)?[A-Za-z_0-9]+:(?=(\s+)?\()/gm,              css: 'functions bold color8' },
            { regex: /(\s+)?(?!else)[A-Za-z_0-9]+(?=(\s+)?\{)/gm,               css: 'functions bold color8' }
			];
	};

	Brush.prototype	= new SyntaxHighlighter.Highlighter();
	Brush.aliases	= ['objc'];

	SyntaxHighlighter.brushes.ObjC = Brush;

	// CommonJS
	typeof(exports) != 'undefined' ? exports.Brush = Brush : null;
})();
