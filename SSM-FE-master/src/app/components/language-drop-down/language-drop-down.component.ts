import { Component, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core'

@Component({
  selector: 'ms-language-drop-down',
  templateUrl: './language-drop-down.component.html',
  styleUrls: ['./language-drop-down.component.scss']
})
export class LanguageDropDownComponent implements OnInit {

   currentLang = 'it';
   selectImage = 'assets/img/italian.png';

	langArray : any [] = [
      {  
         img:"assets/img/en.png",
         name:"English",
         value	: "en"
      },     
      {  
         img:"assets/img/italian.png",
         name:"Italiano",
         value:"it"
      }
   ];

	constructor(public translate : TranslateService) { }

	ngOnInit() {
	}

   //setLang method is used to set the language into template.
   setLang(lang) {
      for(let data of this.langArray) {
         if(data.value == lang) {
            this.selectImage = data.img;
            break;
         }
      }
      this.translate.use(lang);
   }
}
