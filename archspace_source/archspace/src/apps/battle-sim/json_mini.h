// json_mini.h - tiny first-party JSON reader for the battle-sim protocol.
//
// Scope: parses the subset emitted by Python's json.dumps (objects, arrays,
// strings with standard escapes, numbers, true/false/null). Not a general
// JSON validator - it's an internal wire format we control on both ends.
// Responses are written by hand (see json_out below), so this only parses.

#if !defined(__BATTLE_SIM_JSON_MINI_H__)
#define __BATTLE_SIM_JSON_MINI_H__

#include <string>
#include <vector>
#include <map>
#include <cstdlib>

class JValue
{
	public:
		enum Type { J_NUL, J_BOOL, J_NUM, J_STR, J_ARR, J_OBJ };

		Type                       type;
		bool                       b;
		double                     num;
		std::string                str;
		std::vector<JValue>        arr;
		std::map<std::string, JValue> obj;

		JValue() : type(J_NUL), b(false), num(0) {}

		bool is_obj()  const { return type == J_OBJ; }
		bool is_arr()  const { return type == J_ARR; }
		bool is_null() const { return type == J_NUL; }

		bool has(const char *aKey) const
		{
			return type == J_OBJ && obj.find(aKey) != obj.end();
		}

		// Object member access; returns a static null JValue when absent.
		const JValue &operator[](const char *aKey) const
		{
			static const JValue Null;
			if (type != J_OBJ) return Null;
			std::map<std::string, JValue>::const_iterator it = obj.find(aKey);
			return (it == obj.end()) ? Null : it->second;
		}

		const JValue &operator[](size_t aIndex) const
		{
			static const JValue Null;
			if (type != J_ARR || aIndex >= arr.size()) return Null;
			return arr[aIndex];
		}

		size_t size() const { return (type == J_ARR) ? arr.size() : 0; }

		long   as_int (long   aDef = 0)        const { return (type == J_NUM) ? (long)num : aDef; }
		double as_num (double aDef = 0)        const { return (type == J_NUM) ? num : aDef; }
		bool   as_bool(bool   aDef = false)    const { return (type == J_BOOL) ? b : aDef; }
		std::string as_str(const char *aDef = "") const { return (type == J_STR) ? str : std::string(aDef); }
};

class JParser
{
	public:
		// Parse aText into aOut. Returns true on success.
		static bool parse(const std::string &aText, JValue &aOut)
		{
			size_t pos = 0;
			skip_ws(aText, pos);
			if (!parse_value(aText, pos, aOut)) return false;
			skip_ws(aText, pos);
			return true; // trailing junk tolerated (one object per line)
		}

	private:
		static void skip_ws(const std::string &s, size_t &p)
		{
			while (p < s.size() &&
				   (s[p] == ' ' || s[p] == '\t' || s[p] == '\n' || s[p] == '\r'))
				p++;
		}

		static bool parse_value(const std::string &s, size_t &p, JValue &out)
		{
			skip_ws(s, p);
			if (p >= s.size()) return false;
			char c = s[p];
			switch (c)
			{
				case '{': return parse_object(s, p, out);
				case '[': return parse_array(s, p, out);
				case '"': out.type = JValue::J_STR; return parse_string(s, p, out.str);
				case 't': case 'f': return parse_bool(s, p, out);
				case 'n': return parse_null(s, p, out);
				default:  return parse_number(s, p, out);
			}
		}

		static bool parse_object(const std::string &s, size_t &p, JValue &out)
		{
			out.type = JValue::J_OBJ;
			p++; // {
			skip_ws(s, p);
			if (p < s.size() && s[p] == '}') { p++; return true; }
			while (p < s.size())
			{
				skip_ws(s, p);
				if (s[p] != '"') return false;
				std::string key;
				if (!parse_string(s, p, key)) return false;
				skip_ws(s, p);
				if (p >= s.size() || s[p] != ':') return false;
				p++;
				JValue val;
				if (!parse_value(s, p, val)) return false;
				out.obj[key] = val;
				skip_ws(s, p);
				if (p >= s.size()) return false;
				if (s[p] == ',') { p++; continue; }
				if (s[p] == '}') { p++; return true; }
				return false;
			}
			return false;
		}

		static bool parse_array(const std::string &s, size_t &p, JValue &out)
		{
			out.type = JValue::J_ARR;
			p++; // [
			skip_ws(s, p);
			if (p < s.size() && s[p] == ']') { p++; return true; }
			while (p < s.size())
			{
				JValue val;
				if (!parse_value(s, p, val)) return false;
				out.arr.push_back(val);
				skip_ws(s, p);
				if (p >= s.size()) return false;
				if (s[p] == ',') { p++; continue; }
				if (s[p] == ']') { p++; return true; }
				return false;
			}
			return false;
		}

		static bool parse_string(const std::string &s, size_t &p, std::string &out)
		{
			out.clear();
			p++; // opening quote
			while (p < s.size())
			{
				char c = s[p++];
				if (c == '"') return true;
				if (c == '\\')
				{
					if (p >= s.size()) return false;
					char e = s[p++];
					switch (e)
					{
						case '"':  out += '"';  break;
						case '\\': out += '\\'; break;
						case '/':  out += '/';  break;
						case 'n':  out += '\n'; break;
						case 't':  out += '\t'; break;
						case 'r':  out += '\r'; break;
						case 'b':  out += '\b'; break;
						case 'f':  out += '\f'; break;
						case 'u':  // \uXXXX: keep ASCII, skip the 4 hex digits
							if (p + 4 <= s.size()) {
								int code = (int)strtol(s.substr(p, 4).c_str(), NULL, 16);
								if (code < 128) out += (char)code;
								p += 4;
							}
							break;
						default:   out += e; break;
					}
				}
				else out += c;
			}
			return false;
		}

		static bool parse_number(const std::string &s, size_t &p, JValue &out)
		{
			size_t start = p;
			if (p < s.size() && (s[p] == '-' || s[p] == '+')) p++;
			bool any = false;
			while (p < s.size() &&
				   ((s[p] >= '0' && s[p] <= '9') || s[p] == '.' ||
					s[p] == 'e' || s[p] == 'E' || s[p] == '+' || s[p] == '-'))
			{ p++; any = true; }
			if (!any) return false;
			out.type = JValue::J_NUM;
			out.num  = strtod(s.substr(start, p - start).c_str(), NULL);
			return true;
		}

		static bool parse_bool(const std::string &s, size_t &p, JValue &out)
		{
			if (s.compare(p, 4, "true") == 0)  { out.type = JValue::J_BOOL; out.b = true;  p += 4; return true; }
			if (s.compare(p, 5, "false") == 0) { out.type = JValue::J_BOOL; out.b = false; p += 5; return true; }
			return false;
		}

		static bool parse_null(const std::string &s, size_t &p, JValue &out)
		{
			if (s.compare(p, 4, "null") == 0) { out.type = JValue::J_NUL; p += 4; return true; }
			return false;
		}
};

#endif // __BATTLE_SIM_JSON_MINI_H__
