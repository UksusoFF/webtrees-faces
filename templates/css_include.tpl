if (document.createStyleSheet) {
    document.createStyleSheet("[@cssPath]"); // For Internet Explorer
} else {
    $("head").append('<link rel="stylesheet" href="[@cssPath]" type="text/css">');
}