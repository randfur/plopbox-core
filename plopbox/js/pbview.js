/*   |
,---.|---.
|   ||   |
|---'`---'
|
PlopBox
View Presenter
*/
"use strict";

var view = {
  // Check Model for Updated Data
  update: function(model, controller) {
    // ?? pageData doesn't live on the model object.
    var pagedata = model.pagedata;

    // Login Page
    switch (pagedata.opcode) {
      case 'LoginPage':

      $("#template-container").loadTemplate("plopbox/templates/login.html", pagedata, {
        success: function () {
          $("#page-container").loadTemplate("#logintemplate"), pagedata, {
            success: function () {
              document.title = "PlopBox - Log In";
              document.getElementById("loginsubmit").addEventListener("click",
              function () {
                controller.postData($("#loginform").serializeArray());
              });
            }
          }
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
            controller.postData($("#puform").serializeArray());
          });
        }
      });
      break;

      // File Index
      case 'FileIndex':
      if (pagedata.statcode == 'ViewDeny') {
        // TODO: File View Permission Failure

      } else if (pagedata.statcode == 'Empty') {
        // TODO: Empty Directory

      } else {

        // Display File Index
        $("#template-container").loadTemplate("plopbox/templates/core.html", pagedata, {
          success: function () {

            document.title = "PlopBox - Browsing " + nav.uri();

            // Page Navigation Buttons
            pagedata.nextButton = 'visibility:hidden;';
            pagedata.prevButton = 'visibility:hidden;';

            if (pagedata.itemcount > pagedata.flimit) {
              if (pagedata.fstart > 0) {
                pagedata.nextButton = 'visibility:visible;';
                document.getElementById("nextButton").addEventListener("click",
                function () {
                  nav.addArgs({"fstart" : str(pagedata.fstart + pagedata.flimit)});
                  controller.getData();
                });
              };
              if ((pagedata.itemcount + pagedata.flimit) > pagedata.fstart && (pagedata.fstart + pagedata.flimit) <= pagedata.itemcount) {
                pagedata.prevButton = 'visibility:visible;';
                document.getElementById("prevButton").addEventListener("click",
                function () {
                  nav.addArgs({"fstart" : string(pagedata.fstart - pagedata.flimit)});
                });
                controller.getData();
              }
            }
          }
        });

        // Sort Scheme
        var arrow = [];
        arrow["up"] = 'mdi mdi-arrow-up-drop-circle';
        arrow["down"] = 'mdi mdi-arrow-down-drop-circle';
        switch (pagedata.sort) {
          case 0:
          pagedata.namesortarrow = arrow["up"];
          document.getElementById("cname").addEventListener("click",
          function () {
            nav.addArgs({"sort" : 1});
            controller.getData();
          });
          break;
          case 1:
          pagedata.namesortarrow = arrow["down"];
          document.getElementById("cname").addEventListener("click",
          function () {
            nav.addArgs({"sort" : 0});
            controller.getData();
          });
          break;
          case 2:
          pagedata.datesortarrow = arrow["up"];
          document.getElementById("cdate").addEventListener("click",
          function () {
            nav.addArgs({"sort" : 3});
            controller.getData();
          });
          break;
          case 3:
          pagedata.datesortarrow = arrow["down"];
          document.getElementById("cdate").addEventListener("click",
          function () {
            nav.addArgs({"sort" : 2});
            controller.getData();
          });
          break;
          case 4:
          pagedata.sizesortarrow = arrow["up"];
          document.getElementById("csize").addEventListener("click",
          function () {
            nav.addArgs({"sort" : 5});
            controller.getData();
          });
          break;
          case 5:
          pagedata.sizesortarrow = arrow["down"];
          document.getElementById("csize").addEventListener("click",
          function () {
            nav.addArgs({"sort" : 4});
            controller.getData();
          });
          break;
        }
      }

      for (i = 0; i < pagedata.itemcount; i++) {
        $("#entry-container").loadTemplate('#entriestemplate', filedata[i], {
          append: true,
          elemPerPage: pagedata.itemcount,
          success: function () {
            console.log("Test");
          }
        });
      };
      break;

      // Settings Page
      case 'SettingsPage':
      // Display Settings Page
      $("#template-container").loadTemplate("plopbox/templates/core.html", pagedata);
      document.title = "PlopBox - Settings";
      break;
    }

    function closeMsg () {
      document.getElementById("msgclose").addEventListener("click",
      function () {
        $("#msg").remove();
        $("#msgclose").remove();
      });
    };

    // Server Message Display
    if (pagedata.statcode == 'Success') {
      // On Success Message
      $("#message-container").loadTemplate("#msgtemplate", {
        msgclass: "msg",
        msgtext: pagedata.msg
      },
      {
        success: closeMsg
      });

    } else if (pagedata.statcode == 'Error') {
      // On Error Message
      $("#message-container").loadTemplate("#msgtemplate", {
        msgclass: "failmsg",
        msgtext: pagedata.msg
      },
      {
        success: closeMsg
      });
    }
  },
};
