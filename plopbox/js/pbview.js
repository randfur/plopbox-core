// PlopBox View Presenter

// Misc. Functions

// Render Page
function pageView (pagedata) {
  // Login Page
  switch (pagedata.opcode) {
    case 'LoginPage':

    $("#template-container").loadTemplate("plopbox/templates/login.html", pagedata, {
      success: function () {
        document.title = "PlopBox - Log In";
        document.getElementById("loginsubmit").addEventListener("click",
        function () {
          postData($("#loginform").serializeArray());
        });
      }
    });
    break;

    // Primary User Creation Page
    case 'PUPage':
    $("#template-container").loadTemplate("plopbox/templates/createpu.html", pagedata, {
      success: function () {
        document.title = "PlopBox - Create Primary User";
        document.getElementById("pusubmit").addEventListener("click",
        function () {
          postData($("#puform").serializeArray());
        });
      }
    });
    break;

    // File Index
    case 'FileIndex':
    if (pagedata.statcode == '03') {
      // TODO: File View Permission Failure

    } else if (pagedata.statcode == '04') {
      // TODO: Empty Directory

    } else {

      // Display File Index
      $("#template-container").loadTemplate("plopbox/templates/core.html", pagedata, {
        success: function () {

          document.title = "PlopBox - Browsing " + pointer;

          // Page Navigation Buttons
          pagedata.nextButton = 'visibility:hidden;';
          pagedata.prevButton = 'visibility:hidden;';
          if (pagedata.itemcount > pagedata.flimit) {
            if (pagedata.fstart > 0) {
              pagedata.nextButton = 'visibility:visible;';
              document.getElementById("nextButton").addEventListener("click",
              function () {
                pointer.args.add(["fstart=" + str(pagedata.fstart + pagedata.flimit]));
                getData();
              });
            }
            if ((pagedata.itemcount + pagedata.flimit) > pagedata.fstart && (pagedata.fstart + pagedata.flimit) <= pagedata.itemcount) {
              pagedata.prevButton = 'visibility:visible;';
              document.getElementById("prevButton").addEventListener("click",
              function () {
                pointer.args.add(["fstart=" + str(pagedata.fstart - pagedata.flimit]));
                getData();
              });
            };
          };

          // Sort Scheme
          var arrow = [];
          arrow["up"] = 'mdi mdi-arrow-up-drop-circle';
          arrow["down"] = 'mdi mdi-arrow-down-drop-circle';
          switch (pagedata.sort) {
            case 0:
            pagedata.namesortarrow = arrow["up"];
            document.getElementById("cname").addEventListener("click",
            function () {
              pointer.args.add("sort=1");
              getData();
            });
            break;
            case 1:
            pagedata.namesortarrow = arrow["down"];
            document.getElementById("cname").addEventListener("click",
            function () {
              pointer.args.add("sort=0");
              getData();
            });
            break;
            case 2:
            pagedata.datesortarrow = arrow["up"];
            document.getElementById("cdate").addEventListener("click",
            function () {
              pointer.args.add("sort=3");
              getData();
            });
            break;
            case 3:
            pagedata.datesortarrow = arrow["down"];
            document.getElementById("cdate").addEventListener("click",
            function () {
              pointer.args.add("sort=2");
              getData();
            });
            break;
            case 4:
            pagedata.sizesortarrow = arrow["up"];
            document.getElementById("csize").addEventListener("click",
            function () {
              pointer.args.add("sort=5");
              getData();
            });
            break;
            case 5:
            pagedata.sizesortarrow = arrow["down"];
            document.getElementById("csize").addEventListener("click",
            function () {
              pointer.args.add("sort=4");
              getData();
            });
            break;
          };
        }
      });

      for (i = 0; i < pagedata.itemcount; i++) {
        $("#entry-container").loadTemplate('#entriestemplate', filedata[i], {
          append: true,
          elemPerPage: pagedata.itemcount,
          success: function () {
            console.log("Test");
          }
        });
      };

    };
    break;

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
