/*   |
,---.|---.
|   ||   |
|---'`---'
|
PlopBox
Supervising Controller
*/

// Controller Object
function controllerObject () {
  var self = this;

  // URI Navigator Object
  function navigatorObject () {
    var self = this;
    this.args = [];
    this.uri = "/pbindex.php";

    // Add an Argument
    this.addargs = function (values) {
      for (key in values) {
        for (i = self.args.length, len = values.length; i < len; i++) {
          if (self.args.indexOf(values[i]) == -1) {
            self.args.add(values[i]);
          } else {
            self.args[i] = values[i];
          };
        };
      };
    };

    // Remove an Argument
    this.removeargs = function (values) {
      for (key in values) {
        for (i = self.args.length, len = values.length; i < len; i++) {
          if (key == self.args[i]) {
            self.args.splice(i, 1);
          };
        };
      };
    };

    // Reset Navigator to default data
    this.reset = function () {
      self.args = [];
      self.uri = "/pbindex.php";
    };

    // Output the URI with or without Arguments
    this.output = function (argsBool = false) {
      if (argsBool = true) {
        return $(this.uri + $(this.args).serialize()).tostring();
      } else {
        return this.uri;
      };
    };

    // Decode and store a Session JWT
    function jwtDecode (token) {
      var dToken = jwt_decode(token);
    };

    // GET Data from the server
    this.getData = function () {
      console.log("GetData= " + navigator.uri);
      $.ajax({
        url: navigator.uri,
        type: "get",
        data: $(navigator.args).serialize(),
        dataType: "json",
        timeout: 30000,
        success: function (json)
        {
          if (json.error) {
            console.log("Error communicating with the server! " + json.error);
          } else {
            console.log(json);
            computeModel(json, navigator);
          };
        }
      });
      return false;
    };

    // POST Data from the server
    this.postData = function (data = "") {
      console.log("PostData= " + navigator.uri);
      $.ajax({
        url: navigator.uri,
        type: "post",
        data: data,
        dataType: "json",
        timeout: 30000,
        success: function (json)
        {
          if (json.error) {
            console.log("Error communicating with the server! " + json.error);
          } else {
            console.log(json);
            computeModel(json, navigator);
          };
        }
      });
      return false;
    };
  };

  // Initialize Model
  this.navigator = navigator = new navigatorObject();
  console.log(this.navigator.uri);
  this.getData();
};
};

// Assemble New Model Data
function computeModel(pagedata, navigator) {
  console.log("ComputeModel= " + navigator.uri);
  // Tokens
  // TODO: Tokens

  // File Index
  if (pagedata.opcode === 'FileIndex') {
    if (pagedata.statcode == 'ViewDeny' || pagedata.statcode == 'Empty') {

    } else {
      // File Entry Object
      function itemEntryObject (fileData, itemcount, ID, navigator) {
        if (fileData['dir']) {
          this.id = ID;
          this.dir = filedata['dir'];
          this.fileName = fileData['name'];
          this.uri = navigator.uri + fileData['path'];
          this.icon = fileData['ficon'];
          this.modTime = fileData['mtime'];
          this.size = fileData['fsize'];
          this.sizefactor = fileData['fsizefactor'];
          this.modTimeUX = filedata['mtimeux'];
        } else {
          this.id = ID;
          this.dir = filedata['dir'];
          this.fileName = fileData['name'];
          this.uri = navigator.uri + fileData['path'];
          this.icon = fileData['ficon'];
          this.modTime = fileData['mtime'];
          this.size = fileData['fsize'];
          this.sizefactor = fileData['fsizefactor'];
          this.modTimeUX = filedata['mtimeux'];
        };
        // Internal Functions
        // Open File
        this.open = function () {
          getFile(self.uri);
        };
        // TODO: this.delete
        // TODO: this.move
      };

      // Generate File Entries
      for (i = 0; i < pagedata.itemcount; i++) {
        pagedata.fileEntries[i] = new itemEntryObject(pagedata.filedata[i], pagedata.itemcount, i, navigator);
      };
    };

    // Navbar
    var uriarray = navigator.uri.split("/");
    var lastnav = "";
    for (i = 0, len = navigator.uri.length; i < len; i++) {
      pagedata.navItems[i] = lastnav + "/" + uriarray[i];
      lastnav =+ uriarray[i];
    };

    // Stage Model Data
    var modelData = {
      opcode: pagedata.opcode,
      statcode: pagedata.statcode,
      msg: pagedata.msg,
      crow: pagedata.crow,
      filedata: pagedata.filedata,
      loc: pagedata.loc,
      sort: pagedata.sort
    };

  } else if (pagedata.opcode == 'LoginPage') {
    var modelData = {
      opcode: pagedata.opcode,
      statcode: pagedata.statcode,
      token: pagedata.token,
      msg: pagedata.msg,
    }
  };
  console.log(pagedata.opcode);

  // Send Model to View Presenter
  pageView(modelData, navigator);
};

// Download a File from the Server
function getFile(uri) {
  console.log("Getting File: " + uri);
};

// Initial Page Load
$(document).ready(
  function() {
    var controller = new controllerObject();
  };
}
);
