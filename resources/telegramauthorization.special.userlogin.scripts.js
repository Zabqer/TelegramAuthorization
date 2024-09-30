(function () {
  const hash = window.location.hash;
  if (hash.startsWith("#tgAuthResult")) {
    const tgdata = hash.substring(14);
    $("div.mw-userlogin-username").parent().append(
      `<input type="hidden" name="tgdata" value="${tgdata}">`,
    );
    $("#mw-input-tgdata").val(tgdata);
    // TODO: this is bad vay to select button
    $("#mw-input-pluggableauthlogin0").click();
  }
})();
