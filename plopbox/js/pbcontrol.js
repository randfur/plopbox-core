//  PlopBox Supervising Controller

// Internal Pointer Object
function pointerObject () {

  this.args = [];
  this.uri = "/pbindex.php";
  // Add an Argument
  this.args.add = function (values) {
    for (key in values) {
      for (i = this.args.length, len = values.length; i < len; i++) {
        if (this.args.indexOf(values[i]) == -1) {
          this.args.add(values[i]);
        } else {
          this.args[i] = values[i];
        };
      };
    };
  };

  // Remove an Argument
  this.args.remove = function (values) {
    for (key in values) {
      for (i = this.args.length, len = values.length; i < len; i++) {
        if (key == this.args[i]) {
          this.args.splice(i, 1);
        };
      };
    };
  };

  // Reset Pointer to default data
  this.reset = function () {
    this.args = [];
    this.uri = "/pbindex.php";
  };

  // Output the URI with or without Arguments
  this.uri.output = function (argsBool = "") {
    if (argsBool = true) {
      return str(this.uri + serialize(this.uriArgs));
    } else {
      return this.uri;
    };
  };
  // Update the URI
  this.uri.update = function (value) {
    this.uri = value;
  };
};

// Assemble New Model Data
function computeModel(pagedata) {
  // Tokens
  // TODO: Tokens

  // File Index
  if (pagedata.opcode === 'FileIndex' && pagedata.statcode != '03' && pagedata.statcode != '04') {
    // File Entry Object
    function itemEntryObject (fileData, itemcount, ID) {
      if (fileData['dir']) {
        this.id = ID;
        this.dir = filedata['dir'];
        this.fileName = fileData['name'];
        this.uri = pointer + fileData['path'];
        this.icon = fileData['ficon'];
        this.modTime = fileData['mtime'];
        this.size = fileData['fsize'];
        this.sizefactor = fileData['fsizefactor'];
        this.modTimeUX = filedata['mtimeux'];
        this.bornTimeUX = Math.floor(Date.now() / 1000);
      } else {
        this.id = ID;
        this.dir = filedata['dir'];
        this.fileName = fileData['name'];
        this.uri = pointer + fileData['path'];
        this.icon = fileData['ficon'];
        this.modTime = fileData['mtime'];
        this.size = fileData['fsize'];
        this.sizefactor = fileData['fsizefactor'];
        this.modTimeUX = filedata['mtimeux'];
        this.bornTimeUX = Math.floor(Date.now() / 1000);
      };
      // Internal Functions
      // Open File
      this.open = function (uri) {
        getData(uri, 'get');
      };
      // Update Object
      this.update = function (modTime, entryTime) {
        if (modTime > entryTime) {

        };
      };
      // TODO: this.delete
      // TODO: this.move
    };

    // Generate File Entries
    for (i = 0; i < pagedata.itemcount; i++) {
      pagedata.fileEntries[i] = new itemEntryObject(pagedata.filedata[i], pagedata.itemcount, i);
    };

    // Navbar
    var uriarray = pointer.split("/");
    var lastnav = "";
    for (i = 0, len = pointer.length; i < len; i++) {
      pagedata.navItems[i] = lastnav + "/" + uriarray[i];
      lastnav =+ uriarray[i];
    };

    // Stage Model Data
    var modelData = {
      opcode: pagedata.opcode,
      statcode: pagedata.statcode,
      msg: pagedata.msg,
      failmsg: pagedata.failmsg,
      crow: pagedata.crow,
      filedata: pagedata.filedata,
      loc: pagedata.loc,
      sort: pagedata.sort
    };

  } else if (pagedata.opcode == 'LoginPage' && pagedata.statcode != '03' && pagedata.statcode != '04') {
    var modelData = {
      opcode: pagedata.opcode,
      token: pagedata.token,
      msg: pagedata.msg,
      failmsg: pagedata.failmsg
    }
  };
  console.log(pagedata.opcode);
  pageView(modelData);
};

// Read New Data
function getData (data = "") {
  $.ajax({
    url: pointer,
    type: "get",
    data: data,
    dataType: "json",
    timeout: 30000,
    success: function (json)
    {
      if (json.error) {
        failmsg("Error communicating with the server! " + json.error);
      } else {
        console.log(json);
        computeModel(json);
      };
    }
  });
  return false;
};

// POST Data to Server
function postData(data) {
  $.ajax({
    url: pointer,
    type: "post",
    data: data,
    dataType: "json",
    timeout: 30000,
    success: function (json)
    {
      if (json.error) {
        failmsg("Error communicating with the server! " + json.error);
      } else {
        console.log(json);
        computeModel(json);
      };
    }
  });
  return false;
};

// Initial Page Load
$(document).ready(function() {
  pointer = new pointerOBject ();
  getData();
});
