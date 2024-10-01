(function () {
  // To hide login form
  // $(".mw-userlogin-username").hide();
  // $(".mw-userlogin-password").hide();
  // $(".mw-userlogin-rememberme").hide();
  // $("#wpLoginAttempt").hide();
  const hash = window.location.hash;
  if (hash.startsWith("#tgAuthResult")) {
    $(".mw-htmlform[name=userlogin]").css({
      opacity: 0.5,
      pointerEvents: "none",
    }).append(
      $("<div> Выполняется вход </div>").css({
        position: "absolute",
        top: "50%",
        left: "50%",
      }),
    );
    const tgdata = hash.substring(14);
    $("#mw-input-tgdata").val(tgdata);
    // TODO: this is bad vay to select button
    $("#mw-input-pluggableauthlogin0").click();
  }
})();
