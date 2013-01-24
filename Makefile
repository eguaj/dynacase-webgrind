VERSION=1.0
RELEASE=1

webinst: tmp tmp/content.tar.gz tmp/info.xml
	tar -C $< -zcf dynacase-webgrind-${VERSION}-${RELEASE}.webinst info.xml content.tar.gz

tmp:
	mkdir $@

tmp/content.tar.gz: src tmp
	tar -C $< -zcf $@ .

tmp/info.xml: info.xml tmp
	cp $< $@
