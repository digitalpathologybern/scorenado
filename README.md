<div align="left">
  <a href="https://github.com/digitalpathologybern/scorenado">
	<img src="./images/scorenado.png" alt="Logo" height="80">
  </a>
</div>

# SCORENADO
A customizable, user-friendly and open-source visual assessment tool for histological slides

## About The Project
- Structured eyeballing
- Built for TMA scoring but not limited to
- Proper data management
- Web based application

### Workflow

1) Create image gallery from TMA and assign unique identifier
2) Import image gallery
3) Define variable parameters
4) Do visual assessment
5) Export data

### Built With
[![Bootstrap][Bootstrap.com]][Bootstrap-url]
[![JQuery][JQuery.com]][JQuery-url]


## Getting Started
This is a preliminary release of Scorenado for demo purposes a full installation guide will follow with the release version.
Scorenado runs on an average webserver.

### Prerequisites
- MySQL database
- PHP
- Webserver (e.g. Apache)
  
### Installation
 - create user table in database
 	```sql
 	(
	 	pid  int auto_increment
		 	primary key,
	 	name text not null,
	 	pw   text not null
 	)
	 	engine = MyISAM
	 	charset = latin1;
 	```
 - copy files and folders to documentRoot
 - update mysql config file `access.inc` and move to upper directory

## Usage

- copy the folder containing the images to score to documentRoot
- set the folder name and a project description in `scorenado.php` 
- define variable parameters in `scorenado.php`
- start scoring
 
## Roadmap

- [x] Preliminary release of the Scorenado core app
- [ ] Release version including installation instructions
- [ ] Add other components (data preparation, TMA dearray, data merge etc.) of the workflow
- [ ] Userinterface for setup
- [ ] Zoom function

## Contact
	
stefan reinhard - stefan.reinhard@unibe.ch

## License

Distributed under the MIT License. See `LICENSE` for more information

<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[Bootstrap.com]: https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white
[Bootstrap-url]: https://getbootstrap.com
[JQuery.com]: https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white
[JQuery-url]: https://jquery.com
