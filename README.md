# itwikinews-rss

## Purpose

**itwikinews-rss** is a tool that automatically generates a RSS feed for the [Italian version of Wikinews](https://it.wikinews.org/), **Wikinotizie**.

## Hosting

The tool is currently hosted on [Wikimedia Tool Labs](https://toolforge.org/) at the following address:
https://itwikinews-rss.toolforge.org/

It was previously hosted on [Wikimedia Toolserver](https://meta.wikimedia.org/wiki/Toolserver).

## Technical details

The tool is written in PHP.
The tool uses the [MediaWiki API](https://www.mediawiki.org/wiki/API:Main_page) to get metadata about the latest articles submitted and a brief excerpt of their text.

The feature fthat allows to fetch extracts of the articles using the API is provided by the [TextExtracts](https://www.mediawiki.org/wiki/Extension:TextExtracts) MediaWiki extension.

## Contacts

You can send any request, bug report, suggestions for improvements or pull request here on GitHub.
Alternatively, you can reach me on [Meta Wikimedia](https://meta.wikimedia.org/wiki/User:Pietrodn).

## License

This software is licensed under the [GNU General Public License, Version 3](https://www.gnu.org/licenses/gpl.html).
You can find a copy if it in the `LICENSE` file.
