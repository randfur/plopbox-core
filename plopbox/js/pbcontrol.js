//  PlopBox Supervising Controller

// Assemble New Model Data
function computeModel(pagedata) {
  // Tokens
  // TODO: Tokens

  // File Index
  if (pagedata.opcode == 'FileIndex' && pagedata.statcode != '03' && pagedata.statcode != '04') {

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
        newData(uri, 'get');
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
    var fileEntry = [];
    for (i = 0; i < pagedata.itemcount; i++) {
      pagedata.fileEntries[i] = new itemEntryObject(pagedata.filedata[i], pagedata.itemcount, i);
    };

    // Navbar
    pagedata.navlinks = "";
    var uriarray = pointer.split("/");
    var navcount = 1;
    var lastnav;
    var navitems = [];
    for (var i = 0, len = pointer.length; i < len; i++) {
      navitems[i] = '<a href="' + '/' + lastnav + uriarray[i] + '">' + uriarray[i] + '</a>';
      lastnav = uriarray[i] + '/';
    };
    for (var i = 0, len = navitems.length; i < len; i++) {
      pagedata.navlinks += navitems[i];
    };

    // Page Navigation Buttons
    pagedata.nextButton = 'hidden';
    pagedata.prevButton = 'hidden';
    if (pagedata.itemcount > pagedata.flimit) {
      if (pagedata.fstart > 0) {
        pagedata.nextButton = 'visible';
      }
      if ((pagedata.itemcount + pagedata.flimit) > pagedata.fstart && (pagedata.fstart + pagedata.flimit) <= pagedata.itemcount) {
        pagedata.prevButton = 'visible';
      };
    };

    // Sort Scheme
    switch (pagedata.sort) {
      case 0:
      pagedata.namesortarrow = 'mdi mdi-arrow-up-drop-circle';
      break;
      case 1:
      pagedata.namesortarrow = 'mdi mdi-arrow-down-drop-circle';
      break;
      case 2:
      pagedata.datesortarrow = 'mdi mdi-arrow-up-drop-circle';
      break;
      case 3:
      pagedata.datesortarrow = 'mdi mdi-arrow-down-drop-circlee';
      break;
      case 4:
      pagedata.sizesortarrow = 'mdi mdi-arrow-up-drop-circle';
      break;
      case 5:
      pagedata.sizesortarrow = 'mdi mdi-arrow-down-drop-circle';
      break;
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
      msg: pagedata.msg,
      failmsg: pagedata.failmsg
    }
  };

  pageView(pagedata, pointer);
};

// FIXME: Fix me you lazy fuck
// Read New Data
function dataHandler (pointer, type, senddata = "") {
  $.ajax({
    url: pointer,
    type: type,
    data: senddata,
    dataType: "json",
    timeout: 30000,
    success: function (json)
    {
      if (json.error) {
        failmsg("Error communicating with the server! " + json.error);
      } else {
        computeModel(json);
      };
    }
  });
};

// Initial Page Load
$(document).ready(function() {
  pointer = '/pbindex.php'
  dataHandler(pointer, 'get');
});

// Event Listeners

// POST Data to Server
function postData(e) {
  if (e.preventDefault) e.preventDefault();
  dataHandler(pointer, 'post', e);
  return false;
}
