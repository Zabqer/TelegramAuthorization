(function () {
  const LS_REMEMBER_CHECKBOX_KEY = "_wiki_auth_remembercheckbox";
  // To hide login form
  // $(".mw-userlogin-username").hide();
  // $(".mw-userlogin-password").hide();
  // $(".mw-userlogin-rememberme").hide();
  // $("#wpLoginAttempt").hide();
  function setLastCheckboxState(st) {
    localStorage.setItem(LS_REMEMBER_CHECKBOX_KEY, st ? "true" : "false");
  }
  function getLastCheckboxState() {
    return localStorage.getItem(LS_REMEMBER_CHECKBOX_KEY) === "true";
  }
  function dropLastCheckboxState() {
    localStorage.removeItem(LS_REMEMBER_CHECKBOX_KEY);
  }
  const hash = window.location.hash;
  if (hash.startsWith("#tgAuthResult")) {
    $(".mw-htmlform[name=userlogin]").css({
      opacity: 0.5,
      pointerEvents: "none",
    }).append(
      $("<div> Выполняется вход через Telegram... </div>").css({
        position: "absolute",
        top: "50%",
        left: "50%",
      }),
    );
    const tgdata = hash.substring(14);
    $("#mw-input-tgdata").val(tgdata);
    $("#wpRemember").prop("checked", getLastCheckboxState());
    dropLastCheckboxState();
    $("#mw-input-pluggableauthlogin0").click();
  } else {
    const rememberCheckbox = $("#wpRemember");
    setLastCheckboxState(rememberCheckbox.prop("checked"));
    rememberCheckbox.on("change", function (event) {
      setLastCheckboxState(event.target.checked);
    });
  }
})();
