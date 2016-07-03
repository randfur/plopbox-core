// PlopBox View Presenter

// Misc. Functions
function msgclose() {
  document.getElementById("msg").style.visibility = "hidden";
  document.getElementById("failmsg").style.visibility = "hidden";
};

// Render Page
function pageView (pagedata, pointer) {
  // Login Page
  switch (pagedata.opcode) {
    case 'LoginPage':

    function submitTest () {
      console.log("Test OK");
    };

    $("#template-container").loadTemplate("plopbox/templates/login.html",
    {
      msg: pagedata.msg,
      failmsg: pagedata.failmsg
    },
  {
  success: function () {
    document.title = "PlopBox - Log In";
    var sbutton = document.getElementById("loginsubmit");
    sbutton.addEventListener("click", submitTest);
  }
});
break;

// Primary User Creation Page
case 'PUPage':
$("#template-container").loadTemplate("plopbox/templates/createpu.html", pagedata);
document.title = "PlopBox - Create Primary User";
break;

// File Index
case 'FileIndex':
if (pagedata.statcode == '03') {
  // TODO: File View Permission Failure

} else if (pagedata.statcode == '04') {
  // TODO: Empty Directory

} else {

  // Display File Index
  $("#template-container").loadTemplate("plopbox/templates/core.html", pagedata);
  document.title = "PlopBox - Browsing: " + pointer;
  pagedata.filesList = "";
  for (i = 0; i < pagedata.itemcount; i++) {
    pagedata.filesList =+ pagedata.fileEntries[i];
    $("#entry-container").loadTemplate('#entriestemplate', filedata);
  };
  break;
};
// Settings Page
case 'SettingsPage':
// Display Settings Page
$("#template-container").loadTemplate("plopbox/templates/core.html", pagedata);
document.title = "PlopBox - Settings";
break;
};

// Server Message Display
if (pagedata.statcode == 'Success') {
  document.getElementById("msg").style.visibility = "visible";
} else if (pagedata.statcode == 'Error') {
  document.getElementById("failmsg").style.visibility = "visible";
};
};
