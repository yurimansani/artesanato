
function setWord() {
   //Take the word typed in and convert to uppercase and get its length
   //If there is no word typed in - automatically use "FAMILY"

   var word;
   var wordLength;

   if (document.getElementById("selectedWord").value !== '') {
      clearWord();
      word = document.getElementById("selectedWord").value;
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
   selectPaspatur();
   selectFrame();
   getLetters(word);

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
      case "1":
         $(".frame").css('background-color','#000000');
         break;
      case "2":
         $(".frame").css('background-color','#ffffff');
         break;
      case "3":
         $(".frame").css('background-color','#5C2100');
         break;
      case "4":
         $(".frame").css('background-color','#fffdd0 ');
         break;
      default:
         $(".frame").css('background-color','#000000');
   }
}

function selectFrame() {
   var frame = $("#frame").val();
   if (frame == "") {
      return;
   }
   switch(frame) {
      case "3":
         $(".letters").css('-webkit-border--image','url(/media/img/molduras/moldura03.png) 4% 1% round');
         $(".letters").css('-o-border-image','url(/media/img/molduras/moldura03.png) 4% 1% round');
         $(".letters").css('border-image','url(/media/img/molduras/moldura03.png) 4% 1% round');
         break;
      case "7":
         $(".letters").css('-webkit-border--image','url(/media/img/molduras/moldura07.png) 4% 1% round');
         $(".letters").css('-o-border-image','url(/media/img/molduras/moldura07.png) 4% 1% round');
         $(".letters").css('border-image','url(/media/img/molduras/moldura07.png) 4% 1% round');
         break;
      default:
         $(".letters").css('-webkit-border--image','url(/media/img/molduras/moldura07.png) 4% 1% round');
         $(".letters").css('-o-border-image','url(/media/img/molduras/moldura07.png) 4% 1% round');
         $(".letters").css('border-image','url(/media/img/molduras/moldura07.png) 4% 1% round');
   }
}

function changeLetter(id, src) {
   $("#image_" + id).attr('src', src);
   $("#choose-letter-modal").html("")

   $('#letter-modal').modal('hide');
}

function addItemCart() {

   var word = document.getElementById("selectedWord").value;
   word = word.toUpperCase();
   word = word.replace(/\s+/g, '');
   var wordArray = word.split("");
   
   var paspatur = $("#paspatur").val();
   if (paspatur == "") {
      return;
   }

   var frame = $("#frame").val();
   if (frame == "") {
      return;
   }

   if (word.length > 14) {
      return;
   }
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
   switch(paspatur) {
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


}

