/**
 * Oli Catalog & Product PDF — product sheet download.
 * Copies the hidden sheet into a print window (A4, no margins) and opens the print dialog.
 */
( function () {
	var i18n = window.olicgProductPdf || {};

	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	function download( button ) {
		var sheet = document.getElementById( 'olicg-pdf-content-' + button.getAttribute( 'data-olicg-pdf' ) );
		if ( ! sheet ) {
			return;
		}

		var title = ( button.getAttribute( 'data-olicg-file' ) || 'Product' ) + ' - ' + ( i18n.sheetLabel || 'Product Sheet' );
		var html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
		html += '<title>' + escapeHtml( title ) + '</title>';
		html += '<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
		html += '<style>';
		html += '* { box-sizing: border-box; margin: 0; padding: 0; }';
		html += 'html, body { height: 100%; font-family: "Space Grotesk", Arial, sans-serif; color: #09090b; background: #fff; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }';
		html += 'table { border-collapse: collapse; }';
		html += 'td { vertical-align: top; }';
		html += 'img { max-width: 100%; height: auto; }';
		html += '@page { margin: 0; size: A4 portrait; }';
		html += '.olicg-pdf-page { width: 210mm; min-height: 100vh; margin: 0 auto; display: flex; flex-direction: column; }';
		html += '</style></head><body>';
		html += '<div class="olicg-pdf-page">' + sheet.innerHTML + '</div>';
		html += '<script>window.onload = function () { setTimeout(function () { window.print(); }, 500); };<\/script>';
		html += '</body></html>';

		var win = window.open( '', '_blank', 'width=900,height=700' );
		if ( ! win ) {
			window.alert( i18n.popupBlocked || 'Please allow pop-ups to download the PDF.' );
			return;
		}
		win.document.write( html );
		win.document.close();
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '[data-olicg-pdf]' ) : null;
		if ( button ) {
			event.preventDefault();
			download( button );
		}
	} );
} )();
