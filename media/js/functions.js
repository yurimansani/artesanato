
function setWord() {
   //Take the word typed in and convert to uppercase and get its length
   //If there is no word typed in - automatically use "FAMILY"

   var word;
   var wordLength;

   if (document.getElementById("palavra").value !== '') {
      clearWord();
      word = document.getElementById("palavra").value;
      word = word.toUpperCase();
   }
   else {
      clearWord();
      word = "DECOPHOTO";
   }
   //Take out any spaces that may have been entered into the word
   word = word.replace(/\s+/g, '');
   wordLength = word.length;

   if (wordLength > 14) {
      return;
   }
   //Call the setLetters function from below, giving it the word typed and its length
   selectFrame();
   selectPaspatur();
   getLetters(word);
   setTimeout(() => { 
    setImageDescripion(word);
}, 1000);

}


function setLetterImage(id,src) {
   $('#image_' + id).attr('src', src);
}

function getLetters(word) {
   var wordLength = word.split("");

   $.each(wordLength, (i,  letter) => { 
      $(".letters").append('<div class="letter col"><img onclick="choosePic(\'' + letter + '\', ' + i +')" id="image_' + i + '"class="letter-img" ></div>');

      $.ajax({
        url: "/media/service/letter.php",
        method: "POST",
        data: { letter : letter, id:i },
        dataType: "json",
        success: (json) => {
         setLetterImage(json.id,json.data);
        }
      });
   });
}

function clearWord() {
   $(".letters").html('');
}


function choosePic(letter, i) {
   $('#letter-modal').modal('show');

   $.ajax({
     url: "/media/service/letter-image.php",
     method: "POST",
     data: { letter : letter, id:i },
     dataType: "json",
     success: (json) => {
      $.each(json.data, (i, image) => {
         $("#choose-letter-modal").append('<img onclick="changeLetter(' + json.id + ', \' ' + image + ' \')" width="100px" src="' + image + '">');
      });
     }
   });
   
}

function iFrame(folderLocation, id) {
   //Creates an iframe that shows all of the images available in a particular folder

   var ifrm = document.createElement("iframe");
   var background = document.createElement("div");
   var img = document.createElement("img");

   ifrm.setAttribute("class", "iframefloat");
   ifrm.setAttribute("id", "iframe" + id);
   background.id = "hide"
   img.src = "graphics/close_button.png";
   img.id = "close";

   ifrm.setAttribute("src","choose_pic.php?folder=" + folderLocation);

   document.body.appendChild(background);
   document.body.appendChild(ifrm);
   document.body.appendChild(img);

   background.onclick = function() {
      document.body.removeChild(background);
      document.body.removeChild(ifrm);
      document.body.removeChild(img);
   };

   img.onclick = function() {
      document.body.removeChild(background);
      document.body.removeChild(ifrm);
      document.body.removeChild(img);
   };
}

function letterSelected(folder, file) {
   var ifrmid = findIframeId();
   var ifrm = document.getElementById(ifrmid);
   var imageReplace = ifrmid.replace("iframe", "");
   var newSrc;
   var background = document.getElementById("hide");
   var close = document.getElementById("close");
   
   newSrc = "letterArtPics/" + folder + "/" + file;

   document.body.removeChild(background);
   document.body.removeChild(close);
   document.body.removeChild(ifrm);
   
   document.getElementById(imageReplace).src = newSrc;
}

function findIframeId() {
   var iframes = document.getElementsByTagName("iframe");
   var number = iframes.length;
   var ifrmid = iframes[number - 1].getAttribute("id");
   
   return ifrmid;
}


function selectPaspatur() {
   var paspatur = $("#paspatur").val();
   if (paspatur == "") {
      return;
   }
   switch(paspatur) {
      case "Preto":
         $(".frame").css('background-color','#000000');
         break;
      case "Branco":
         $(".frame").css('background-color','#ffffff');
         break;
      case "Chocolate":
         $(".frame").css('background-color','#5C2100');
         break;
      case "Creme":
         $(".frame").css('background-color','#fffdd0 ');
         break;
      default:
         $(".frame").css('background-color','#000000');
   }
}


function selectPhotoColor() {
   var photoColor = $("#cor_da_foto").val();
   if (photoColor == "") {
      return;
   }
   switch(photoColor) {
      case "Colorida":
        $("img.letter-img").css('filter','grayscale(0)');
        $("img.letter-img").css('filter','sepia(0)');
        break;
      case "Preto e branco":
        $("img.letter-img").css('filter','sepia(0)');
        $("img.letter-img").css('filter','grayscale(100)');
        break;
      case "Sepia":
        $("img.letter-img").css('filter','grayscale(0)');
        $("img.letter-img").css('filter','sepia(100)');
        break;
   }
}

function selectFrame() {
   var frame = $("#moldura").val();
   if (frame == "") {
    $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura1.png") 250 stretch');
    $(".frame").css('-o-border-image','url(/media/img/molduras/moldura1.png) 250 stretch');
    $(".frame").css('border-image','url(/media/img/molduras/moldura1.png) 250 stretch');
   } 
   switch(frame) {
      case "Moldura 1":
         $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura1.png") 250 stretch');
         $(".frame").css('-o-border-image','url(/media/img/molduras/moldura1.png) 250 stretch');
         $(".frame").css('border-image','url(/media/img/molduras/moldura1.png) 250 stretch');
         break;
      case "Moldura 2":
         $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura2.png") 250 stretch');
         $(".frame").css('-o-border-image','url(/media/img/molduras/moldura2.png) 250 stretch');
         $(".frame").css('border-image','url(/media/img/molduras/moldura2.png) 250 stretch');
         break;
      case "Moldura 3":
         $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura3.png") 250 stretch');
         $(".frame").css('-o-border-image','url(/media/img/molduras/moldura3.png) 250 stretch');
         $(".frame").css('border-image','url(/media/img/molduras/moldura3.png) 250 stretch');
         break;
      case "Moldura 4":
         $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura4.png") 250 stretch');
         $(".frame").css('-o-border-image','url(/media/img/molduras/moldura4.png) 250 stretch');
         $(".frame").css('border-image','url(/media/img/molduras/moldura4.png) 250 stretch');
         break;
      case "Moldura 5":
         $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura5.png") 250 stretch');
         $(".frame").css('-o-border-image','url(/media/img/molduras/moldura5.png) 250 stretch');
         $(".frame").css('border-image','url(/media/img/molduras/moldura5.png) 250 stretch');
         break;
      case "Moldura 6":
         $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura6.png") 250 stretch');
         $(".frame").css('-o-border-image','url(/media/img/molduras/moldura6.png) 250 stretch');
         $(".frame").css('border-image','url(/media/img/molduras/moldura6.png) 250 stretch');
         break;
      case "Moldura 7":
         $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura7.png") 250 stretch');
         $(".frame").css('-o-border-image','url(/media/img/molduras/moldura7.png) 250 stretch');
         $(".frame").css('border-image','url(/media/img/molduras/moldura7.png) 250 stretch');
         break;
      default:
         $(".frame").css('-webkit-border-image: url("/media/img/molduras/moldura7.png") 250 stretch');
         $(".frame").css('-o-border-image','url(/media/img/molduras/moldura7.png) 250 stretch');
         $(".frame").css('border-image','url(/media/img/molduras/moldura7.png) 250 stretch');
         break;
   }
}

function changeLetter(id, src) {
   $("#image_" + id).attr('src', src);
   $("#choose-letter-modal").html("")

   $('#letter-modal').modal('hide');
}

function addItemCart() {

   var word = document.getElementById("palavra").value;
   word = word.toUpperCase();
   word = word.replace(/\s+/g, '');
   var wordArray = word.split("");
   
   var paspatur = $("#paspatur").val();
   if (paspatur == "") {

      return;
   }
   console.log(paspatur);

   var frame = $("#frame").val();
   if (frame == "") {
      return;
   }
   console.log(frame);

   if (word.length > 14) {
      return;
   }
   console.log(wordArray);
   $.each(wordArray, (i, letter) => {
      var productLetterCode;

      switch(letter) {
        case 'A':
          productLetterCode = '433'
          break;
        case 'B':
          productLetterCode = '434'
          break;
        case 'C':
          productLetterCode = '435'
          break;
        case 'D':
          productLetterCode = '436'
          break;
        case 'E':
          productLetterCode = '437'
          break;
        case 'F':
          productLetterCode = '438'
          break;
        case 'G':
          productLetterCode = '439'
          break;
        case 'H':
          productLetterCode = '440'
          break;
        case 'I':
          productLetterCode = '441'
          break;
        case 'J':
          productLetterCode = '442'
          break;
        case 'K':
          productLetterCode = '443'
          break;
        case 'L':
          productLetterCode = '444'
          break;
        case 'M':
          productLetterCode = '445'
          break;
        case 'N':
          productLetterCode = '446'
          break;
        case 'O':
          productLetterCode = '447'
          break;
        case 'P':
          productLetterCode = '448'
          break;
        case 'Q':
          productLetterCode = '449'
          break;
        case 'R':
          productLetterCode = '450'
          break;
        case 'S':
          productLetterCode = '451'
          break;
        case 'T':
          productLetterCode = '452'
          break;
        case 'U':
          productLetterCode = '453'
          break;
        case 'V':
          productLetterCode = '454'
          break;
        case 'W':
          productLetterCode = '455'
          break;
        case 'X':
          productLetterCode = '456'
          break;
        case 'Y':
          productLetterCode = '457'
          break;
        case 'Z':
          productLetterCode = '458'
          break;
      }

      $.ajax({
        url: "/?add-to-cart=" + productLetterCode,
        method: "GET",
        dataType: "json",
      });

   });
   var productPaspaturCode
   switch(frame) {
      case "1":
         productPaspaturCode = '461';
         break;
      case "2":
         productPaspaturCode = '462';
         break;
      case "3":
         productPaspaturCode = '463';
         break;
      case "4":
         productPaspaturCode = '464';
         break;
   }

   $.ajax({
     url: "/?add-to-cart=" + productPaspaturCode,
     method: "GET",
     dataType: "json",
   });

   var productFrameCode
   switch(paspatur) {
      case "1":
         productFrameCode = '426';
         break;
      case "2":
         productFrameCode = '427';
         break;
      case "3":
         productFrameCode = '428';
         break;
      case "4":
         productFrameCode = '429';
         break;
      case "5":
         productFrameCode = '425';
         break;
      case "6":
         productFrameCode = '426';
         break;
      case "7":
         productFrameCode = '427';
         break;
   }

   $.ajax({
     url: "/?wc-ajax=add_to_cart" ,
     method: "POST",
     dataType: "json", 
     data: { product_sku : null, product_id:425,quantity:1, variation_id : productFrameCode},
     success: (json) => {
        alert(json);
      }
   });

}

function setImageDescripion(word) {
  var wordLength = word.split("");
  $("#quadrojs").val("");
  var textjs = '';

  $.each(wordLength, (i,  letter) => {
    var src = $('img#image_' + i).attr('src');
    textjs += 'Letra ' + letter + ' -> ' + '<img src="'+ src + '" width="50px">' + '<br>' ;
    
  });
    $("#quadrojs").val(textjs);

}

setTimeout(() => { 
  $('#palavra').blur(() => {
    setWord();
  });
  $('#paspatur').change(() => {
    selectPaspatur();
  });
  $('#cor_da_foto').change(() => {
    selectPhotoColor();
  });
  $('#moldura').change(() => {
    selectFrame();
  });
  setWord();  
}, 1000);
