$(document).ready(function(){
	
$(document).on('change', '#file_advert', function(){
  var name = document.getElementById("file_advert").files[0].name;
  var form_data = new FormData();
  var ext = name.split('.').pop().toLowerCase();
  if(jQuery.inArray(ext, "jpg|png|gif|jpeg|mp4|ts|mpeg|mkv".split("|")) == -1) 
  {
   alert("Invalid File! Only jpg|png|gif|jpeg|mp4|ts|mpeg|mkv");
  }
  var oFReader = new FileReader();
  oFReader.readAsDataURL(document.getElementById("file_advert").files[0]);
  var f = document.getElementById("file_advert").files[0];
  var fsize = f.size||f.fileSize;
 
  {
   form_data.append("file", document.getElementById('file_advert').files[0]);
   $.ajax({
    url:"/?do=/plugin&id=advert&mode=addurl&upload=file",
    method:"POST",
    data: form_data,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend:function(){
    
    },   
    success:function(data)
    {
     $('#payd_url').val(data);
	 if(jQuery.inArray(ext, "jpg|png|gif|jpeg".split("|")) == -1) $('#video_adv').html("<video width=240 controls autoplay=true height=180 src='"+data+"'></video>");
	 else $('#video_adv').html("<img width=240 autoplay=true height=180 src='"+data+"'>");
	 $('#video_adv').show();
    },
	error: function (error) {
		$('#payd_url').val(error.statusText);
		alert('error: ' + JSON.stringify(error));
	}
   });
  }
 }); 
 
 
});