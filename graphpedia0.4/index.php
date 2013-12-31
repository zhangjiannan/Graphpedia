<?php

error_reporting(E_ALL);
require 'db/connect.php';
require 'facebook-php-sdk-master/src/facebook.php';

// Create our Application instance (replace this with your appId and secret).
$facebook = new Facebook(array(
));

// Get User ID
$user = $facebook->getUser();

// We may or may not have this data based on whether the user is logged in.
//
// If we have a $user id here, it means we know the user is logged into
// Facebook, but we don't know if the access token is valid. An access
// token is invalid if the user logged out of Facebook.

if ($user) {
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    $user_profile = $facebook->api('/me');
      //If user is new, add profile into db
        $trimuserid=trim($user);
        $result = $db->query("SELECT * FROM user WHERE user_id='{$trimuserid}'");
        if($result){
          if($result->num_rows){
              //Do Nothing for now
          } else{
              //Escape to avoid injection
              $user_id=trim($user);
              $user_name=trim($user_profile['name']);
              $first_name=trim($user_profile['first_name']);
              $last_name=trim($user_profile['last_name']);
              $email=trim($user_profile['email']);

              if($insert = $db->query("
                  INSERT INTO user (user_name,first_name,last_name,email,user_id)
                  VALUES ('{$user_name}','{$first_name}','{$last_name}','{$email}','{$user_id}')
                ")){
                      
                } else{
                    echo "error";
                }
              
          }
      }
  } catch (FacebookApiException $e) {
    error_log($e);
    $user = null;
  }
}

// Login or logout url will be needed depending on current user state.
if ($user) {
  $logoutUrl = "logout.php";
} else {
  $statusUrl = $facebook->getLoginStatusUrl();
  $loginUrl = $facebook->getLoginUrl(
      array('scope' => 'email')
    );
}

?>

<!DOCTYPE html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
  <head>
    <script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="//code.jquery.com/jquery-1.4.2.min.js"></script>
    <script src="//tinymce.cachefly.net/4.0/tinymce.min.js"></script>
    <script>
            tinymce.init({selector:'textarea'});
    </script>
    <link href="css/kdisplay.css" rel="stylesheet" type="text/css">
    <link href="bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
    <style type="text/css">

        line.link {
          stroke: #ccc;
        }

        circle.node {
          fill: #000;
          stroke: #fff;
          stroke-width: 1.5px;
        }

    </style>
  <title>KnowledgeNet Prototype 0.4</title>
  </head>
  <body>
    <div id = "wrapper_id" class = "wrapper">
      <h1>Graphpedia</h1>
      <div id = "search_id" class = "search">
              <input type="text" name="searchstring" id="ksearchstring" class="searchbox"/>
              <input type="button" id="ksearch" value="Search" class="btn btn-large btn-primary"/>
              <input type="button" id="reload" onclick="window.location.reload();" value="New Search" class="btn btn-large btn-warning"/>
                  <?php if ($user): ?>
                    <a href="<?php echo $logoutUrl; ?>" >Logout</a>
                  <?php else: ?>
                      <a href="<?php echo $loginUrl; ?>">Login with Facebook</a>
                  <?php endif ?>

                  <?php if ($user): ?>
                    <img src="https://graph.facebook.com/<?php echo $user; ?>/picture">

                  <?php
                    echo "Hi, ",$user_profile['first_name'];
                  ?>
                  <?php else: ?>
                    <strong><em>If you want to save your graphpedia search and notes, simply login!</em></strong>
                  <?php endif ?>
              <div id = "contact_id" class = "contact">
                  <span>
                    Contact:
                    For any ideas to organize and viz info from wikipedia, please ping me !! :)
                    To: Jiannan. jn.zhang610@gmail.com
                  </span>
              </div>
      </div>

    <div id = "kdisplay_id" class = "kdisplay">

    <div id = "kgraph_id" class = "kgraph">

    <script type="text/javascript">

        var w = 500,
            h = 500,
            r = d3.scale.sqrt().domain([0, 20000]).range([0, 20]);

        var forcenodeLength = 0;

        var force = d3.layout.force()
            .gravity(.04)
            .charge(-200)
            .linkDistance(100)
            .size([w, h]);

        var forcenodes = [];
        var forcelinks = [];
        var node = [],link = [],text = [];

        var nodeLabels = [];
        var descriptiveText = [];

        var svg;
        var expandIndex=-1;
        var notebookswitcher=0;

        force
              .nodes(forcenodes)
              .links(forcelinks)

        $(document).ready(function(){
              $("#textinfo_id").click(function(event) {
                  console.log(event.target.nodeName);
              })
          });


        ///////////////////////////////////Render Graph from URL //////////////////
        function rendergraphxml(url){
            d3.xml(url,function(xml) {

          //currentMotherNode keeps the mother node of this search
          var nodes = d3.select(xml).selectAll("*")[0];
          var currentMotherNode = forcenodeLength;
          var lastSearchPointer = forcenodeLength;
          var flag_mothersetup = 0;
          if(nodes.length==0){
              return;
          }

          //Importing xml data into d3 has problem, can only import all tags first, then filter the ones I need
          for(i=0;i<nodes.length;i++){
              if(nodes[i].nodeName=="Result"){
                  var textContent = nodes[i].textContent.split('\n');
                  var label = textContent[1].trim();
                  var index_temp;
                  if((index_temp=nodeLabels.indexOf(label))>-1){
                      if(flag_mothersetup==0){
                          currentMotherNode=index_temp;
                          flag_mothersetup=1;
                      }
                      continue;
                  }
                  nodeLabels[forcenodeLength] = textContent[1].trim();
                  descriptiveText[forcenodeLength] = textContent[4].trim();

                  forcenodes.push({
                      type: "circle",
                      size: 5
                  });
                  forcenodeLength++;
              }
          }

          //Push wanted links into link array
          for(i=lastSearchPointer;i<forcenodes.length;i++){
              forcelinks.push({
                  "source" : forcenodes[currentMotherNode],
                  "target" : forcenodes[i]
              })
          }

          startforce();
        });
    }

//////////////////////////////// Function to start force //////////////////////////

        function startforce(){
            force.start();
            if(svg!=undefined){
              d3.select("svg").remove();
            }
            svg = d3.select("#kgraph_id").append("svg:svg")
              .attr("width", w)
              .attr("height", h)
              .append('svg:g')
                  .call(d3.behavior.zoom().on("zoom", redraw))

            console.log(svg.selectAll(".link"));
            console.log(force.links());
            
            link = svg.selectAll(".link")
                .data(force.links())
                .enter().append("line")
                .attr("class", "link")
                .attr("x1", function(d) { return d.source.x; })
                .attr("y1", function(d) { return d.source.y; })
                .attr("x2", function(d) { return d.target.x; })
                .attr("y2", function(d) { return d.target.y; });

            console.log(link);
            
            node = svg.selectAll(".circle")
                .data(force.nodes())
                .enter().append("circle")
                    .attr("class","node")
                    .attr("cx", function(d) { return d.x; })
                    .attr("cy", function(d) { return d.y; })
                    .attr("r",8)
                    .call(force.drag);

  /*          node.append("text")
                .attr("x",function(d) { return d.x; })
                .attr("dy",".35em")
                .text(function(d) {return "a";})*/


            text = svg.selectAll(".text")
                .data(force.nodes())
                .enter().append("text")
                    .attr("class","text")
                    .attr("x",function(d) { return d.x; })
                    .attr("y",function(d) { return d.y; })
                    .text(function(d) {return nodeLabels[d.index];});

            force.on("tick", tick);


  /////////////////////////////////////// Selection Actions //////////////////////

            svg.selectAll("circle").on("click",function(d) {
                d3.select(this)
                    .style("fill","red");

                console.log(nodeLabels[d.index]);
                $("#textinfo_id").empty();
                $("#textinfo_id")
                    .append("<iframe id='textiframe_id' src="+"'http://en.wikipedia.org/wiki/"+ nodeLabels[d.index].replace(/ /g,"_").trim()
                          +"' frameborder='0'"+
                          " width='800' height='600'"+"></iframe>");

                //Cors is really a bad thing, although it brings security!
                /*var proxy = "http://www.jiannanweb.com/knet/ba-simple-proxy.php",
                url = proxy +"?"+"http://en.wikipedia.org/wiki/"+ nodeLabels[d.index].replace(/ /g,"_").trim();

                $("#textinfo_id").load(url);*/

                document.getElementById("nodeselect_id").innerHTML=nodeLabels[d.index];
                var expand = document.getElementById("expandoption_id");
                expand.style.display="block";

                //Related variable updates // Share with add and delete node index as well
                expandIndex=d.index;
            });

            svg.selectAll("circle").on("mouseover",function(d) {
                d3.select(this)
                    .style("fill","blue");

                var box = document.getElementById("popup");
                box.style.left=(d.x+30)+"px";
                box.style.right=(d.y+30)+"px";
                box.style.display="block";
                document.getElementById("popuptext").innerHTML=descriptiveText[d.index];
            })
                .on("mouseout",function(d) {
                      d3.select(this).style("fill","black");
                      var box = document.getElementById("popup");
                      box.style.display="none";
                      document.getElementById("popuptext").innerHTML="";

                      //var expand = document.getElementById("expandoption_id");
                      //expand.style.display="none";
                });
        }

////////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////// Function for adding single nodes /////////////////
        function addNode(labelNew,indexOld){
            if((index_temp=nodeLabels.indexOf(labelNew))==-1){
                  forcenodes.push({
                      type: "circle",
                      size: 5
                  });
                  nodeLabels[forcenodeLength]=labelNew;
                  descriptiveText[forcenodeLength] = "No Text Yet"; //TODO: get data from dbpedia
                  //Add descriptive text if there is
                  var url="http://lookup.dbpedia.org/api/search.asmx/KeywordSearch?QueryClass=&MaxHits=10&QueryString="+labelNew;
                  d3.xml(url,function(xml) {
                      //currentMotherNode keeps the mother node of this search
                      var nodes = d3.select(xml).selectAll("*")[0];
                      for(i=0;i<nodes.length;i++){
                          if(nodes[i].nodeName=="Result"){
                              var textContent = nodes[i].textContent.split('\n');
                              descriptiveText[forcenodeLength] = textContent[4].trim();
                              break;
                            }
                      }
                    });
                  forcenodeLength++;

                  forcelinks.push({
                      "source" : forcenodes[indexOld],
                      "target" : forcenodes[forcenodeLength-1]
                  })

                  //////////////////////////////!!!!!!!!!

                  startforce();

                  //////////////////////////////!!!!!!!!!
            }
        };


//////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////functions for this page/////////////////////////////////////////

        $(document).ready(function(){
            $("#ksearch").click(function(){
                rendergraphxml("http://lookup.dbpedia.org/api/search.asmx/KeywordSearch?QueryClass=&MaxHits=10&QueryString="+ksearchstring.value,"application/xml");
          });
        });

        $(document).ready(function(){
            $("#expandbtn_id").click(function(){
                if(expandIndex>-1){
                    rendergraphxml("http://lookup.dbpedia.org/api/search.asmx/KeywordSearch?QueryClass=&MaxHits=10&QueryString="+nodeLabels[expandIndex],"application/xml");
                }

                var expand = document.getElementById("expandoption_id");
                expand.style.display="none";
            });

            $("#addbtn_id").click(function(){
                if(expandIndex>-1){
                    addNode(addnodetext_id.value,expandIndex);
                }

                var expand = document.getElementById("expandoption_id");
                expand.style.display="none";
            });
            $("#canceladd_id").click(function(){
                var expand = document.getElementById("expandoption_id");
                expand.style.display="none";
            });
            $("#takenotes_id").click(function(){
                var notebook = document.getElementById("notebookarea_id");
                if(notebookswitcher==0){
                    $("#takenotesbtn_id").attr('value', 'Hide Notebook');
                    notebook.style.display="block";
                    notebookswitcher=1;
                } else {
                    $("#takenotesbtn_id").attr('value', 'Open Notebook');
                    notebook.style.display="none";
                    notebookswitcher=0;
                    var content = tinymce.get('notebook_id').getContent();
                }
                
            });
            $("#savegraphbtn_id").click(function(){


                
       /*         var str_json_node = JSON.stringify(forcenodes);
                console.log(str_json_node);
                forcenodes=JSON.parse(str_json_node);
                console.log(forcenodes);

                var str_json_link = JSON.stringify(forcelinks);
                console.log(str_json_link);
                forcenodes=JSON.parse(str_json_link);
                console.log(forcelinks);

                force = d3.layout.force()
                    .gravity(.04)
                    .charge(-200)
                    .linkDistance(100)
                    .size([w, h]);

                force
                    .nodes(forcenodes)
                    .links(forcelinks);*/

                /*force
                    .nodes(forcenodes)
                    .links(forcelinks)*/

             //   startforce();

            /*    if (!$user){
                    alert("Login is required.");
                } else{
                    var json_data;
                }*/
            });
        });

        function tick(){
            forcenodes[0].x = w / 2;
            forcenodes[0].y = h*3 / 5;

            link.attr("x1", function(d) { return d.source.x; })
                .attr("y1", function(d) { return d.source.y; })
                .attr("x2", function(d) { return d.target.x; })
                .attr("y2", function(d) { return d.target.y; });
          
            node.attr("cx", function(d) { return d.x; })
                .attr("cy", function(d) { return d.y; });

            text.attr("x",function(d) { return d.x + 10; })
                .attr("y",function(d) { return d.y + 10; })
                .attr("font-size","small")
                .attr("dy",".10em");
        }
  
        function redraw() {
          svg.attr("transform",
              "translate(" + d3.event.translate + ")"
              + " scale(" + d3.event.scale + ")");
        }

    </script>

    </div>

    <div id = "textinfo_id" class = "textinfo">
        <p>Wiki Reader</p>
    </div>
    
    </div>

    <div id='popup' style='display: none; position: absolute; left: 100px; top: 50px; border: solid black 1px; padding: 10px; background-color: rgb(255,255,255); text-align: left; font-size: 15px; width: 160px;'>
    <span id='popuptext'></span>
    </div>

    <div id='expandoption_id' class = 'expandoption' style='display: none; position: absolute; left: 25px; top: 190px; border: solid black 1px; padding: 10px; background-color: rgb(255,255,255); text-align: left; font-size: 15px; width: 300px; height:80px;'>
        <div>
            Selected: <span id="nodeselect_id"></span>
        </div>
        <div>
            <input type="button" id="expandbtn_id" value="Auto-expand" class="btn btn-mini btn-info"/>
            <input type="button" id="addbtn_id" value="Add Node" class="btn btn-mini btn-info"/>
            <input type="button" id="canceladd_id" value="Cancel" class="btn btn-mini btn-info"/>
        </div>
        <div>
            <span>Node Name: </span>
            <input type="text" name="addnodetext" id="addnodetext_id" class="addnode"/>
        </div>
    </div>
    <div id='takenotes_id' class='takenotes' style='position: absolute; left: 350px; top: 200px;'>
        <input type="button" id="savegraphbtn_id" value="Save Graph" class="btn btn-mini btn-info"/>
        <input type="button" id="takenotesbtn_id" value="Open Notebook" class="btn btn-mini btn-info"/>
    </div>
    <div id="notebookarea_id" class="notebookarea" style="display:none; position: absolute; left:525px; top:190px; width: 400px; height: 100px;">
        <textarea id="notebook_id" class="notebook" style="width: 100%; height: 100%; margin:0; border:1px solid #CCC;"></textarea>
    </div>
  </div>

  </body>
</html>
