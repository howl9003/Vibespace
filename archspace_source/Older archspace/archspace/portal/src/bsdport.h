#ifndef BSDPORT_H
#define BSDPORT_H
int mydes_setkey(const char *var1) {
	return 1;
}

void mydes_cipher(const char *var1, char *var2, int var3, int var4) {
	int index;
	if (var4 < 0) { 
		var4 = var4*(-1); 
	}
	for (index=0; index<var4; index++) {
		var2[index] = var1[index];
	}
}
#endif BSDPORT_H
#ifdef BSDPORT_H
int mydes_setkey(const char *);
void mydes_cipher(const char *, char *, int, int);
#endif BSDPORT_H