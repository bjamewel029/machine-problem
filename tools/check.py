"""Compare out/<dataset>/ with expected/<dataset>/. Optional helper; any diff tool does the same."""
import sys, os

def lines(path):
    with open(path, encoding='utf-8-sig') as f:
        return [l.rstrip('\r\n') for l in f]

def main():
    if len(sys.argv) != 2:
        print('usage: python tools/check.py <small|medium>'); return 2
    ds = sys.argv[1]
    here = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    bad = 0
    for name in ('alarms.csv', 'answers.csv'):
        exp_p = os.path.join(here, 'expected', ds, name)
        got_p = os.path.join(here, 'out', ds, name)
        if not os.path.exists(got_p):
            print(f'MISSING  out/{ds}/{name}'); bad += 1; continue
        exp, got = lines(exp_p), lines(got_p)
        while got and got[-1] == '': got.pop()
        while exp and exp[-1] == '': exp.pop()
        if exp == got:
            print(f'OK       {name}  ({len(exp) - 1} rows)'); continue
        bad += 1
        print(f'DIFFERS  {name}  expected {len(exp) - 1} rows, got {len(got) - 1}')
        shown = 0
        for i in range(max(len(exp), len(got))):
            e = exp[i] if i < len(exp) else '<nothing>'
            g = got[i] if i < len(got) else '<nothing>'
            if e != g:
                print(f'  line {i + 1}: expected  {e}')
                print(f'  {" " * len(str(i + 1))}       got       {g}')
                shown += 1
                if shown == 5: break
    return 1 if bad else 0

if __name__ == '__main__':
    sys.exit(main())
